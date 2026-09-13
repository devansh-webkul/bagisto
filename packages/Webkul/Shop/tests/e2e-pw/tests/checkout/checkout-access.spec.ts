import { test } from "../../setup";
import { ProductCreatePage } from "../../pages/admin/catalog/products/ProductCreatePage";
import { ProductListPage } from "../../pages/admin/catalog/products/ProductListPage";
import { SimpleProductCheckout } from "../../pages/shop/checkout/product-types/SimpleProductCheckout";
import { setConfigSwitch, setMinimumOrder } from "../../utils/admin";
import { uniqueStamp } from "../../utils/faker";
import { formatPrice } from "../../utils/prices";

const PRICE = 199;
const MINIMUM_ORDER_AMOUNT = 500;
const CHECKOUT_CONFIG_PATH = "admin/configuration/sales/checkout";
const GUEST_CHECKOUT_FIELD = "sales[checkout][shopping_cart][allow_guest_checkout]";

test.describe("checkout access", () => {
    let productName: string;
    let productListPage: ProductListPage;

    test.beforeEach(async ({ adminPage }) => {
        productListPage = new ProductListPage(adminPage);
        productName = `simple-${uniqueStamp()}`;

        await new ProductCreatePage(adminPage).createProduct({
            type: "simple",
            sku: `SKU-${uniqueStamp()}`,
            name: productName,
            shortDescription: "Short desc",
            description: "Full desc",
            price: PRICE,
            weight: 1,
            inventory: 100,
        });
    });

    test.afterEach(async () => {
        await productListPage.deleteProductsIfPresent([productName]);
    });

    test("should send a guest to sign in while guest checkout is disabled", async ({
        adminPage,
        shopPage,
    }) => {
        const original = await setConfigSwitch(
            adminPage,
            CHECKOUT_CONFIG_PATH,
            GUEST_CHECKOUT_FIELD,
            false,
        );

        try {
            const checkout = new SimpleProductCheckout(shopPage);

            await checkout.addSimpleProductToCart(productName);
            await checkout.attemptCheckoutFromCart();

            await checkout.expectSignInRequired();
        } finally {
            await setConfigSwitch(adminPage, CHECKOUT_CONFIG_PATH, GUEST_CHECKOUT_FIELD, original);
        }
    });

    test("should keep a guest on the cart while the order is below the minimum amount", async ({
        adminPage,
        shopPage,
    }) => {
        const original = await setMinimumOrder(adminPage, {
            enabled: true,
            amount: String(MINIMUM_ORDER_AMOUNT),
        });

        try {
            const checkout = new SimpleProductCheckout(shopPage);

            await checkout.addSimpleProductToCart(productName);
            await checkout.attemptCheckoutFromCart();

            await checkout.expectStillOnCart();
            await checkout.expectMinimumOrderNotice(formatPrice(MINIMUM_ORDER_AMOUNT));
        } finally {
            await setMinimumOrder(adminPage, original);
        }
    });
});
