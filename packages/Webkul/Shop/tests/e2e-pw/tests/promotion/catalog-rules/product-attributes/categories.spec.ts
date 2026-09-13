import { ProductListPage } from "../../../../pages/admin/catalog/products/ProductListPage";
import { ProductEditPage } from "../../../../pages/admin/catalog/products/ProductEditPage";
import type { BaseProduct } from "../../../../pages/types/product.types";
import { uniqueStamp } from "../../../../utils/faker";
import { test } from "../../../../setup";
import { ProductCreatePage } from "../../../../pages/admin/catalog/products/ProductCreatePage";
import { RuleDeletePage } from "../../../../pages/admin/marketing/promotion/RuleDeletePage";
import { RuleCreatePage } from "../../../../pages/admin/marketing/promotion/RuleCreatePage";
import { RuleApplyPage } from "../../../../pages/shop/rules/RuleApplyPage";
import { Page } from "@playwright/test";

const PRODUCT_CATEGORY = "Mens";
const OTHER_CATEGORY = "Womens";

let product: BaseProduct;
let createdRules: string[];

test.beforeEach(async ({ adminPage }) => {
    createdRules = [];

    product = await new ProductCreatePage(adminPage).createProduct({
        type: "simple",
        sku: `SKU-${uniqueStamp()}`,
        name: `Simple-${uniqueStamp()}`,
        shortDescription: "Short desc",
        description: "Full desc",
        price: 199,
        weight: 1,
        inventory: 100,
    });

    const productEditPage = new ProductEditPage(adminPage);

    await productEditPage.openProduct(product.name);
    await productEditPage.assignCategory(PRODUCT_CATEGORY);
    await productEditPage.save();
});

test.afterEach(async ({ adminPage }) => {
    try {
        await new RuleDeletePage(adminPage).deleteCatalogRulesIfPresent(createdRules);
    } finally {
        await new ProductListPage(adminPage).deleteProductsIfPresent([product.name]);
    }
});

async function createCategoryRule(
    adminPage: Page,
    { operator, category, type }: { operator: string; category: string; type: string },
): Promise<number> {
    const ruleCreatePage = new RuleCreatePage(adminPage);

    const rule = await ruleCreatePage.catalogRuleCreationFlow();
    createdRules.push(rule.name);

    const discountValue = await ruleCreatePage.addCondition({
        scopeSku: product.sku,
        attribute: "product|category_ids",
        operator,
        checkboxSelect: category,
        couponType: type,
    });

    await ruleCreatePage.saveCatalogRule();

    return discountValue ?? 0;
}

const testCases = [
    {
        operator: "{}",
        category: PRODUCT_CATEGORY,
        label: "contains the product category",
        type: "percentage",
    },
    {
        operator: "{}",
        category: PRODUCT_CATEGORY,
        label: "contains the product category",
        type: "fixed",
    },
    {
        operator: "!{}",
        category: OTHER_CATEGORY,
        label: "does not contain another category",
        type: "percentage",
    },
    {
        operator: "!{}",
        category: OTHER_CATEGORY,
        label: "does not contain another category",
        type: "fixed",
    },
];

test.describe("catalog rules", () => {
    test.describe("product attribute conditions", () => {
        for (const tc of testCases) {
            test(`should discount the product when category condition -> ${tc.label} (${tc.type})`, async ({
                adminPage,
                shopPage,
            }) => {
                const discountValue = await createCategoryRule(adminPage, tc);

                await new RuleApplyPage(shopPage).verifyCatalogRule({
                    productName: product.name,
                    price: product.price ?? 0,
                    value: discountValue,
                    type: tc.type,
                });
            });
        }

        test("should leave the price untouched when the product is not in the category", async ({
            adminPage,
            shopPage,
        }) => {
            await createCategoryRule(adminPage, {
                operator: "{}",
                category: OTHER_CATEGORY,
                type: "percentage",
            });

            await new RuleApplyPage(shopPage).expectNoCatalogDiscount(
                product.name,
                product.price ?? 0,
            );
        });
    });
});
