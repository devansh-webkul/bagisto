import { test } from "../../setup";
import { ProductCreatePage } from "../../pages/admin/catalog/products/ProductCreatePage";
import { ProductEditPage } from "../../pages/admin/catalog/products/ProductEditPage";
import { ProductListPage } from "../../pages/admin/catalog/products/ProductListPage";
import type { BaseProduct } from "../../pages/admin/types/product.types";
import { generateDescription, uniqueStamp } from "../../utils/faker";

const PRICE = 199;

function buildProduct(): BaseProduct & { sku: string } {
    const stamp = uniqueStamp();

    return {
        sku: `SKU-${stamp}`,
        name: `Product ${stamp}`,
        shortDescription: generateDescription(60),
        description: generateDescription(100),
        price: PRICE,
        weight: 1,
        inventory: 100,
    };
}

test.describe("product management", () => {
    test.setTimeout(180000);

    let productCreatePage: ProductCreatePage;
    let productEditPage: ProductEditPage;
    let productListPage: ProductListPage;
    let created: string[];

    test.beforeEach(async ({ adminPage }) => {
        productCreatePage = new ProductCreatePage(adminPage);
        productEditPage = new ProductEditPage(adminPage);
        productListPage = new ProductListPage(adminPage);
        created = [];
    });

    test.afterEach(async () => {
        await productListPage.deleteProductsIfPresent(created);
    });

    test("should create a simple product and list it with its sku, price and status", async () => {
        const product = buildProduct();
        created.push(product.name);

        await productCreatePage.createSimpleProduct(product);

        await productListPage.expectProductDetails(product.name, {
            sku: product.sku,
            price: "199.00",
            status: "Active",
        });
    });

    test("should reject a product whose sku is already used", async () => {
        const product = buildProduct();
        created.push(product.name);

        await productCreatePage.createSimpleProduct(product);
        await productCreatePage.attemptCreateProduct(product.sku);

        await productCreatePage.expectCreateRefused("The sku has already been taken.");
        await productListPage.expectProductCountForSku(product.sku, 1);
    });

    test("should reject a product without a name and price", async () => {
        const product = buildProduct();
        created.push(product.name);

        await productCreatePage.createSimpleProduct(product);
        await productEditPage.submitWithoutRequiredFields(product.name);

        await productEditPage.expectValidationError("The Name field is required");
        await productEditPage.expectValidationError("The Price field is required");
        await productEditPage.expectStillOnEditForm();
        await productListPage.expectProductDetails(product.name, { price: "199.00" });
    });

    test("should update the price and keep it after reload", async () => {
        const product = buildProduct();
        created.push(product.name);

        await productCreatePage.createSimpleProduct(product);
        await productEditPage.updatePrice(product.name, "249");

        await productEditPage.expectPriceInEditForm(product.name, "249");
        await productListPage.expectProductDetails(product.name, { price: "249.00" });
    });

    test("should disable only the selected products through the mass action", async () => {
        const product = buildProduct();
        const untouched = buildProduct();
        created.push(product.name, untouched.name);

        await productCreatePage.createSimpleProduct(product);
        await productCreatePage.createSimpleProduct(untouched);
        await productListPage.massUpdateStatus([product.name], "Disable");

        await productListPage.expectProductDetails(product.name, { status: "Disable" });
        await productListPage.expectProductDetails(untouched.name, { status: "Active" });
    });

    test("should copy a product and list the copy beside the original", async () => {
        const product = buildProduct();
        created.push(product.name);

        await productCreatePage.createSimpleProduct(product);

        const copyName = await productListPage.copyProduct(product.name);
        created.push(copyName);

        await productListPage.expectProductListed(copyName);
        await productListPage.expectProductDetails(product.name, { sku: product.sku });
    });

    test("should mass delete only the selected products", async () => {
        const product = buildProduct();
        const untouched = buildProduct();
        created.push(product.name, untouched.name);

        await productCreatePage.createSimpleProduct(product);
        await productCreatePage.createSimpleProduct(untouched);
        await productListPage.massDeleteProducts([product.name]);

        await productListPage.expectProductAbsent(product.name);
        await productListPage.expectProductListed(untouched.name);
    });
});
