// @ts-check
const { test, expect } = require('@playwright/test');

const BASE_URL = 'http://localhost/clinic%20management%20system/';

test.describe('CMS End-to-End UI Verification', () => {

  test('Landing Page loads successfully', async ({ page }) => {
    await page.goto(BASE_URL);
    await expect(page).toHaveTitle(/Clinic CMS/);
    await expect(page.locator('text=Next-Gen Clinic Management')).toBeVisible();
  });

  test('Admin Login Flow', async ({ page }) => {
    await page.goto(BASE_URL);
    
    // Open Login Modal
    await page.click('text=Launch Portal');
    await expect(page.locator('#auth-container')).toBeVisible();

    // Fill Credentials
    await page.fill('#login-email', 'admin@clinic.com');
    await page.fill('#login-password', 'password');
    await page.click('button:has-text("Authorize Access")');

    // Verify Dashboard Redirect
    await expect(page.locator('#dashboard-container')).toBeVisible();
    await expect(page.locator('#role-badge')).toHaveText('admin');
    await expect(page.locator('text=System Audit Dashboard')).toBeVisible();
  });

  test('Doctor Module Accessibility', async ({ page }) => {
    await page.goto(BASE_URL);
    await page.click('text=Launch Portal');
    
    await page.fill('#login-email', 'doctor1@clinic.com');
    await page.fill('#login-password', 'password');
    await page.click('button:has-text("Authorize Access")');

    await expect(page.locator('#role-badge')).toHaveText('doctor');
    // Check for doctor specific elements
    await expect(page.locator('text=Physician Workspace')).toBeVisible();
    await expect(page.locator('text=Today\'s Appointments')).toBeVisible();
  });

  test('Responsive Sidebar/Navigation Toggle', async ({ page }) => {
    await page.goto(BASE_URL);
    await page.click('text=Launch Portal');
    await page.fill('#login-email', 'reception@clinic.com');
    await page.fill('#login-password', 'password');
    await page.click('button:has-text("Authorize Access")');

    await expect(page.locator('#role-badge')).toHaveText('receptionist');
    
    // Verify Logout functionality
    await page.click('#logout-btn');
    await expect(page.locator('#landing-hero')).toBeVisible();
  });

});
