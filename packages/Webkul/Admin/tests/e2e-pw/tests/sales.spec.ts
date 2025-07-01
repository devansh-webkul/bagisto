import { test, expect } from "../setup";
import {
    generateCompanyName,
    generateName,
    generateFirstName,
    generateLastName,
    generateEmail,
    generatePhoneNumber,
    generateStreetAddress,
    generateDescription,
    generateRandomNumericString,
} from "../utils/faker";

export async function generateOrder(adminPage) {
    await adminPage.goto("admin/sales/orders");
    await adminPage.getByRole('button', { name: 'Create Order' }).click();

    /**
     * Create customer.
     */
    await adminPage.getByRole('button', { name: 'Create Customer' }).click();

    const firstNameInput = adminPage.locator('input[name="first_name"]:visible');
    await firstNameInput.fill(generateFirstName());

    const lastNameInput = adminPage.locator('input[name="last_name"]:visible');
    await lastNameInput.fill(generateLastName());

    const emailInput = adminPage.locator('input[name="email"]:visible');
    await emailInput.fill(generateEmail());

    const phoneInput = adminPage.locator('input[name="phone"]:visible');
    await phoneInput.fill(generatePhoneNumber());

    const genderSelect = adminPage.locator('select[name="gender"]:visible');
    await genderSelect.selectOption("Other");

    await phoneInput.press("Enter");

    /**
     * Select product and add to cart.
     */
    await adminPage.getByRole('button', { name: 'Add Product' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by name' }).click();
    await adminPage.getByRole('textbox', { name: 'Search by name' }).fill('Arctic');
    await adminPage.locator('form').filter({ hasText: '$14.00 Qty Add To Cart' }).getByRole('button').click();

    /**
     * Add billing address.
     */
    await adminPage.getByText('Add Address').click();
    await adminPage.getByRole('textbox', { name: 'Company Name' }).click();
    await adminPage.getByRole('textbox', { name: 'Company Name' }).fill(generateCompanyName());
    await adminPage.getByRole('textbox', { name: 'First Name' }).click();
    await adminPage.getByRole('textbox', { name: 'First Name' }).fill(generateFirstName());
    await adminPage.getByRole('textbox', { name: 'Last Name' }).click();
    await adminPage.getByRole('textbox', { name: 'Last Name' }).fill(generateLastName());
    await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
    await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill(generateEmail());
    await adminPage.getByRole('textbox', { name: 'Street Address' }).click();
    await adminPage.getByRole('textbox', { name: 'Street Address' }).fill(generateStreetAddress());
    await adminPage.locator('select[name="billing\\.country"]').selectOption('IN');
    await adminPage.locator('select[name="billing\\.state"]').selectOption('DL');
    await adminPage.getByRole('textbox', { name: 'City' }).click();
    await adminPage.getByRole('textbox', { name: 'City' }).fill('Test City');
    await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).click();
    await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).fill('111111');
    await adminPage.getByRole('textbox', { name: 'Telephone' }).click();
    await adminPage.getByRole('textbox', { name: 'Telephone' }).fill(generatePhoneNumber());
    await adminPage.getByRole('button', { name: 'Save' }).click();

    /**
     * Add shipping address.
     */
    await adminPage.locator('#use_for_shipping').nth(1).click();

    await adminPage.getByText('Add Address').click();
    await adminPage.getByRole('textbox', { name: 'Company Name' }).click();
    await adminPage.getByRole('textbox', { name: 'Company Name' }).fill(generateCompanyName());
    await adminPage.getByRole('textbox', { name: 'Company Name' }).press('Tab');
    await adminPage.getByRole('textbox', { name: 'Vat ID' }).press('Tab');
    await adminPage.getByRole('textbox', { name: 'First Name' }).fill(generateFirstName());
    await adminPage.getByRole('textbox', { name: 'First Name' }).press('Tab');
    await adminPage.getByRole('textbox', { name: 'Last Name' }).fill(generateLastName());
    await adminPage.getByRole('textbox', { name: 'Last Name' }).press('Tab');
    await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill(generateEmail());
    await adminPage.getByRole('textbox', { name: 'email@example.com' }).press('Tab');
    await adminPage.getByRole('textbox', { name: 'Street Address' }).fill(generateStreetAddress());
    await adminPage.locator('select[name="shipping\\.country"]').selectOption('IN');
    await adminPage.locator('select[name="shipping\\.state"]').selectOption('DL');
    await adminPage.getByRole('textbox', { name: 'City' }).click();
    await adminPage.getByRole('textbox', { name: 'City' }).fill('Test City');
    await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).click();
    await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).fill('111111');
    await adminPage.getByRole('textbox', { name: 'Telephone' }).click();
    await adminPage.getByRole('textbox', { name: 'Telephone' }).fill(generatePhoneNumber());
    await adminPage.getByRole('button', { name: 'Save' }).click();

    /**
     * Now clicking on proceed button.
     */
    await adminPage.getByRole('button', { name: 'Proceed' }).click();

    /**
     * Select payment method.
     */
    await adminPage.locator('label').filter({ hasText: 'Flat Rate$10.00Flat Rate' }).locator('label').click();
    await adminPage.locator('label').filter({ hasText: 'Cash On Delivery' }).locator('label').click();

    /**
     * Place order.
     */
    await adminPage.getByRole('button', { name: 'Place Order' }).click();
}

test.describe("sales management", () => {
    test("should be able to create orders", async ({ adminPage }) => {
        await generateOrder(adminPage);
    });

    test("should be able to comment on order", async ({ adminPage }) => {
        await generateOrder(adminPage);

        await adminPage.goto("admin/sales/orders");
        await adminPage.locator(".row > div:nth-child(4) > a").first().click();

        await adminPage.getByRole('textbox', { name: 'Write your comment' }).click();
        await adminPage.getByRole('textbox', { name: 'Write your comment' }).fill(generateDescription());
        await adminPage.getByRole('button', { name: '' }).click();
        await adminPage.getByRole('button', { name: 'Submit Comment' }).click();

        await expect(adminPage.locator("#app")).toContainText("Comment added successfully.");
    });

    test("should be able to reorder", async ({ adminPage }) => {
        await generateOrder(adminPage);

        await adminPage.goto("admin/sales/orders");
        await adminPage.locator(".row > div:nth-child(4) > a").first().click();
        await adminPage.getByRole("link", { name: " Reorder" }).click();

        await expect(adminPage.getByText("Cart Items")).toBeVisible();

        /**
         * Add billing address.
         */
        await adminPage.getByText('Add Address').click();
        await adminPage.getByRole('textbox', { name: 'Company Name' }).click();
        await adminPage.getByRole('textbox', { name: 'Company Name' }).fill(generateCompanyName());
        await adminPage.getByRole('textbox', { name: 'First Name' }).click();
        await adminPage.getByRole('textbox', { name: 'First Name' }).fill(generateFirstName());
        await adminPage.getByRole('textbox', { name: 'Last Name' }).click();
        await adminPage.getByRole('textbox', { name: 'Last Name' }).fill(generateLastName());
        await adminPage.getByRole('textbox', { name: 'email@example.com' }).click();
        await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill(generateEmail());
        await adminPage.getByRole('textbox', { name: 'Street Address' }).click();
        await adminPage.getByRole('textbox', { name: 'Street Address' }).fill(generateStreetAddress());
        await adminPage.locator('select[name="billing\\.country"]').selectOption('IN');
        await adminPage.locator('select[name="billing\\.state"]').selectOption('DL');
        await adminPage.getByRole('textbox', { name: 'City' }).click();
        await adminPage.getByRole('textbox', { name: 'City' }).fill('Test City');
        await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).click();
        await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).fill('111111');
        await adminPage.getByRole('textbox', { name: 'Telephone' }).click();
        await adminPage.getByRole('textbox', { name: 'Telephone' }).fill(generatePhoneNumber());
        await adminPage.getByRole('button', { name: 'Save' }).click();

        /**
         * Add shipping address.
         */
        await adminPage.locator('#use_for_shipping').nth(1).click();

        await adminPage.getByText('Add Address').click();
        await adminPage.getByRole('textbox', { name: 'Company Name' }).click();
        await adminPage.getByRole('textbox', { name: 'Company Name' }).fill(generateCompanyName());
        await adminPage.getByRole('textbox', { name: 'Company Name' }).press('Tab');
        await adminPage.getByRole('textbox', { name: 'Vat ID' }).press('Tab');
        await adminPage.getByRole('textbox', { name: 'First Name' }).fill(generateFirstName());
        await adminPage.getByRole('textbox', { name: 'First Name' }).press('Tab');
        await adminPage.getByRole('textbox', { name: 'Last Name' }).fill(generateLastName());
        await adminPage.getByRole('textbox', { name: 'Last Name' }).press('Tab');
        await adminPage.getByRole('textbox', { name: 'email@example.com' }).fill(generateEmail());
        await adminPage.getByRole('textbox', { name: 'email@example.com' }).press('Tab');
        await adminPage.getByRole('textbox', { name: 'Street Address' }).fill(generateStreetAddress());
        await adminPage.locator('select[name="shipping\\.country"]').selectOption('IN');
        await adminPage.locator('select[name="shipping\\.state"]').selectOption('DL');
        await adminPage.getByRole('textbox', { name: 'City' }).click();
        await adminPage.getByRole('textbox', { name: 'City' }).fill('Test City');
        await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).click();
        await adminPage.getByRole('textbox', { name: 'Zip/Postcode' }).fill('111111');
        await adminPage.getByRole('textbox', { name: 'Telephone' }).click();
        await adminPage.getByRole('textbox', { name: 'Telephone' }).fill(generatePhoneNumber());
        await adminPage.getByRole('button', { name: 'Save' }).click();

        /**
         * Now clicking on proceed button.
         */
        await adminPage.getByRole('button', { name: 'Proceed' }).click();

        /**
         * Select payment method.
         */
        await adminPage.locator('label').filter({ hasText: 'Flat Rate$10.00Flat Rate' }).locator('label').click();
        await adminPage.locator('label').filter({ hasText: 'Cash On Delivery' }).locator('label').click();

        /**
         * Place order.
         */
        await adminPage.getByRole('button', { name: 'Place Order' }).click();
    });

    test("should be able to create invoice", async ({ adminPage }) => {
        await generateOrder(adminPage);

        await adminPage.goto("admin/sales/orders");
        await adminPage.locator(".row > div:nth-child(4) > a").first().click();

        await adminPage.getByText('Invoice', { exact: true }).click();
        await adminPage.locator('#can_create_transaction').nth(1).click();
        await adminPage.getByRole('button', { name: 'Create Invoice' }).click();

        await expect(adminPage.locator("#app")).toContainText("Invoice created successfully");
    });

    test("should be able to send duplicate invoice", async ({ adminPage }) => {
        await adminPage.goto("admin/sales/invoices");
        await adminPage.waitForSelector(".cursor-pointer.rounded-md.text-2xl.transition-all.icon-view");
        await adminPage.locator(".cursor-pointer.rounded-md.text-2xl.transition-all.icon-view").first().click();

        await adminPage.getByRole("button", { name: " Send Duplicate Invoice" }).click();
        await adminPage.getByRole("button", { name: "Send", exact: true }).click();

        await expect(adminPage.locator("#app")).toContainText("Invoice sent successfully");
    });

    test("should be able to create shipment", async ({ adminPage }) => {
        await generateOrder(adminPage);

        await adminPage.goto("admin/sales/orders");
        await adminPage.locator(".row > div:nth-child(4) > a").first().click();

        await adminPage.getByText('Ship', { exact: true }).click();
        await adminPage.getByRole('textbox', { name: 'Carrier Name' }).click();
        await adminPage.getByRole('textbox', { name: 'Carrier Name' }).fill(generateName());
        await adminPage.getByRole('textbox', { name: 'Carrier Name' }).press('Tab');
        await adminPage.getByRole('textbox', { name: 'Tracking Number' }).fill(generateRandomNumericString(10));
        await adminPage.locator('[id="shipment\\[source\\]"]').selectOption('1');
        await adminPage.getByRole('button', { name: 'Create Shipment' }).click();

        await expect(adminPage.locator("#app")).toContainText("Shipment created successfully");
    });

    test("should be able to create refund", async ({ adminPage }) => {
        await generateOrder(adminPage);

        /**
         * Create invoice first.
         */
        await adminPage.goto("admin/sales/orders");
        await adminPage.locator(".row > div:nth-child(4) > a").first().click();

        await adminPage.getByText('Invoice', { exact: true }).click();
        await adminPage.locator('#can_create_transaction').nth(1).click();
        await adminPage.getByRole('button', { name: 'Create Invoice' }).click();

        await expect(adminPage.locator("#app")).toContainText("Invoice created successfully");

        /**
         * Create refund.
         */
        await adminPage.goto("admin/sales/orders");
        await adminPage.locator(".row > div:nth-child(4) > a").first().click();

        await adminPage.getByText('Refund', { exact: true }).first().click();
        await adminPage.getByRole('button', { name: 'Refund' }).click();

        await expect(
            adminPage.locator("p", { hasText: "Refund created successfully" })
        ).toBeVisible();
    });

    test("should be able to cancel order", async ({ adminPage }) => {
        await generateOrder(adminPage);

        await adminPage.goto("admin/sales/orders");
        await adminPage.locator(".row > div:nth-child(4) > a").first().click();

        await adminPage.getByRole('link', { name: 'Cancel' }).click();
        await adminPage.getByRole('button', { name: 'Agree', exact: true }).click();

        await expect(adminPage.locator("#app")).toContainText("Order cancelled successfully");
    });
});
