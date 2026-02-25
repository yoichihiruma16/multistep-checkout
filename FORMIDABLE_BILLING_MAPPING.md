# Formidable Forms to WooCommerce Billing Mapping

## Overview
This implementation automatically maps data from the Formidable Forms "Abonnee" form to WooCommerce billing and shipping address fields during checkout.

## Mapping Details

### Implementation Location
File: `/includes/Checkout_Form.php`
Method: `populate_billing_from_formidable()`

### Form Fields Mapping

#### From "Abonnee" Form to WooCommerce Billing Fields:

| Formidable Field | Field Type | WooCommerce Field | Notes |
|---|---|---|---|
| Name | name | billing_first_name, billing_last_name | Splits name into first and last name |
| Email | email | billing_email | |
| Telefoonnummer (mobiel) | phone | billing_phone | Prefers mobile, falls back to fixed phone |
| Telefoonnummer (vast) | phone | billing_phone | Used if mobile phone not available |
| Adres (line1) | address | billing_address_1 | Street name with house number if available |
| Adres (line2) | address | billing_address_1 | House number (combined with street) |
| Adres (city) | address | billing_city | City name |
| Adres (zip) | address | billing_postcode | Postal code |
| Adres (country) | address | billing_country | Country code/name |

### Shipping Address
As requested, the shipping address is automatically set to be the same as the billing address:
- shipping_first_name
- shipping_last_name
- shipping_address_1
- shipping_city
- shipping_postcode
- shipping_country

## How It Works

1. **Form Submission**: When a customer fills out the "Abonnee" form during checkout, the form data is captured and stored.

2. **Order Processing**: During the `woocommerce_checkout_update_order_meta` hook (at priority 10, then 11 for billing population):
   - The `save_formidable_data_to_order()` method stores all form data as order meta
   - The `populate_billing_from_formidable()` method extracts data from the "Abonnee" form and populates WooCommerce fields

3. **Field Extraction**: The method:
   - Identifies the "Abonnee" form by name matching
   - Extracts each field type (name, email, phone, address)
   - Performs necessary parsing (e.g., splitting full name into first/last)
   - Sets the order's billing and shipping address fields

4. **Data Handling**:
   - Address data is parsed from JSON format if present
   - Phone field prefers mobile over fixed phone
   - Name field is intelligently split into first and last names
   - Empty fields are skipped to preserve existing data

## Testing

To verify the mapping is working:

1. Add a product with the "Abonnee" form assigned
2. Go through checkout and fill out the form
3. Check the order details - billing address fields should be auto-populated
4. Verify the order admin panel shows the form data in the "Form Information" meta box

## Notes

- The "Abonnee" form name is matched case-insensitively
- The method runs after the main form data is saved
- All data is sanitized before being stored in order fields
- The implementation respects the WooCommerce order object API
- Shipping address is automatically synchronized with billing address
