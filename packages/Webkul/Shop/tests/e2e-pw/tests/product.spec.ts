import { test } from "../setup";
import { ProductCreatePage } from "../pages/admin/catalog/products/ProductCreatePage";
import { ProductEditPage } from "../pages/admin/catalog/products/ProductEditPage";
import { ProductListPage } from "../pages/admin/catalog/products/ProductListPage";
import { CartPage } from "../pages/shop/CartPage";
import { ProductPage } from "../pages/shop/ProductPage";
import type { BaseProduct } from "../pages/types/product.types";
import { login, register } from "../utils/customer";
import { uniqueStamp } from "../utils/faker";

function buildSimpleProduct(inventory: number): BaseProduct {
    return {
        type: "simple",
        sku: `SKU-${uniqueStamp()}`,
        name: `Simple-${uniqueStamp()}`,
        shortDescription: "Short desc",
        description: "Full desc",
        price: 199,
        weight: 1,
        inventory,
    };
}

test.describe("product availability", () => {
    let productListPage: ProductListPage;
    let created: string[];

    test.beforeEach(async ({ adminPage }) => {
        productListPage = new ProductListPage(adminPage);
        created = [];
    });

    test.afterEach(async () => {
        await productListPage.deleteProductsIfPresent(created);
    });

    test("should not offer add to cart for an out of stock product", async ({
        adminPage,
        shopPage,
    }) => {
        const product = await new ProductCreatePage(adminPage).createProduct(
            buildSimpleProduct(0),
        );
        created.push(product.name);

        const productPage = new ProductPage(shopPage);

        await productPage.expectListingAddToCartDisabled(product.name);

        await productPage.open(product.name);

        await productPage.expectAddToCartDisabled();
    });

    test("should hide a disabled product from the storefront", async ({
        adminPage,
        shopPage,
    }) => {
        const product = await new ProductCreatePage(adminPage).createProduct(
            buildSimpleProduct(100),
        );
        created.push(product.name);

        const productPage = new ProductPage(shopPage);

        await productPage.open(product.name);

        const productEditPage = new ProductEditPage(adminPage);

        await productEditPage.openProduct(product.name);
        await productEditPage.setToggle("status", false);
        await productEditPage.save();

        await productPage.expectAbsentFromListing(product.name);
        await productPage.expectNotFound(product.urlKey ?? "");
    });

    test("should keep a guest cart after the customer signs in", async ({
        adminPage,
        shopPage,
    }) => {
        const product = await new ProductCreatePage(adminPage).createProduct(
            buildSimpleProduct(100),
        );
        created.push(product.name);

        const credentials = await register(shopPage);
        const cartPage = new CartPage(shopPage);

        await cartPage.addProductToCart(product.name);
        await login(shopPage, credentials);
        await cartPage.openCart();

        await cartPage.expectCartQuantity(product.name, 1);
    });
});
