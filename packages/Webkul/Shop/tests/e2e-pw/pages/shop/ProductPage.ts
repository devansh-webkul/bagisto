import { expect, type Page } from "@playwright/test";
import { BasePage } from "../BasePage";

export class ProductPage extends BasePage {
    constructor(page: Page) {
        super(page);
    }

    private get searchInput() {
        return this.page.getByPlaceholder("Search products here");
    }

    private get productForm() {
        return this.page.locator('form:has(input[name="product_id"])');
    }

    private get addToCartButton() {
        return this.productForm.getByRole("button", { name: "Add To Cart" });
    }

    private productCard(productName: string) {
        return this.page
            .locator("div.group")
            .filter({ has: this.page.locator(`p:text-is("${productName}")`) });
    }

    private cardAddToCartButton(productName: string) {
        return this.productCard(productName).getByRole("button", {
            name: "Add To Cart",
        });
    }

    async search(productName: string): Promise<void> {
        await this.visit("");
        await this.searchInput.fill(productName);
        await this.searchInput.press("Enter");

        await expect(this.page).toHaveURL(/search\?/);
    }

    async open(productName: string): Promise<void> {
        await this.search(productName);

        const card = this.productCard(productName);

        await expect(card).toHaveCount(1);
        await card.getByRole("link", { name: productName }).click();

        await expect(this.productForm).toBeVisible();
    }

    async expectListingAddToCartDisabled(productName: string): Promise<void> {
        await this.search(productName);

        await expect(this.productCard(productName)).toHaveCount(1);
        await expect(this.cardAddToCartButton(productName)).toBeDisabled();
    }

    async expectAddToCartDisabled(): Promise<void> {
        await expect(this.addToCartButton).toBeDisabled();
    }

    async expectAbsentFromListing(productName: string): Promise<void> {
        await this.search(productName);

        await expect(this.productCard(productName)).toHaveCount(0);
    }

    async expectNotFound(urlKey: string): Promise<void> {
        const response = await this.page.goto(urlKey);

        expect(response?.status()).toBe(404);
    }
}
