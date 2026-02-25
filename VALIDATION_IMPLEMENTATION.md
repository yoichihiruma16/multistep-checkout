# Conditional Form Validation Implementation

## Overview
Created a comprehensive form validation and conditional rendering system for the multistep-checkout plugin that:
1. Conditionally displays forms based on selected products
2. Validates only relevant forms
3. Handles conditional field requirements

## Files Created/Modified

### 1. **New File: `includes/Form_Validator.php`**
A static utility class that handles:
- **Product-to-Form Mapping**: Maps which forms should display based on product attributes
- **Form Requirements**: Defines required fields and conditional logic for each form
- **Validation Logic**: Validates form submissions against configured requirements
- **Conditional Display**: Determines which forms should be visible to users

**Key Features:**
```php
// Form mapping based on product type
'with-nursing' => array( 4, 5, 6, 10, 11 ),  // Full suite of forms
'with-social' => array( 4, 5, 7 ),           // Simplified forms
'default' => array( 3, 4, 5, 7, 10 ),        // Standard forms

// Form-specific requirements
3 => array(
    'name' => 'Abonnee',
    'required_fields' => [ 'field_2wnsd', 'field_f9iyg', 'field_eqy7c', ... ],
    'conditional' => false,
)
```

### 2. **Modified: `includes/Checkout_Form.php`**
Added validation hook:
- New method: `validate_checkout_forms()`
- Hooked to: `woocommerce_checkout_process`
- Validates only forms relevant to selected products
- Displays WooCommerce notices for validation errors

### 3. **Modified: `multistep-checkout.php`**
- Added require statement for `Form_Validator.php`
- Form_Validator is loaded as a static utility (not instantiated)

## How It Works

### Form Selection Flow:
1. **User selects products** → Bundle item selections trigger AJAX
2. **Cart is populated** → Product attributes/meta determine form set
3. **Checkout page loads** → Only relevant forms are displayed
4. **User fills forms** → Only relevant forms are validated

### Validation Flow:
1. **On checkout submission** → `validate_checkout_forms()` fires
2. **For each cart form ID:**
   - Check if form should be displayed (product-based)
   - Validate required fields from XML config
   - Handle conditional fields (show/hide based on parent values)
3. **If errors found:**
   - Add WooCommerce error notices
   - Prevent order placement

## Form Configuration

### Current Form Mappings:

| Form ID | Name | Required Fields | Conditional |
|---------|------|-----------------|-------------|
| 3 | Abonnee | Salutation, Name, Birthdate, Email, Phones, Address | No |
| 4 | Contactpersoon | Salutation, Name, Phone, Email, Relation | No |
| 5 | Alarmopvolgers | Salutation, Name, Phone | No |
| 6 | Verpleegkundige | Medical data agreement checkbox | No |
| 7 | Woningtoegang | Central door, Key box, Home care | Yes* |
| 10 | Aanvullende Informatie | Terms checkboxes | Yes* |
| 11 | Alarm via Verzekering | Provider selection | No |

*Conditional = Has child fields that only become required based on parent answers

## Extending the System

### To Add New Product Types:
```php
// In Form_Validator.php, add to $product_form_mapping:
'new-product-type' => array( 3, 4, 7 ), // Relevant form IDs
```

### To Add New Validation Rules:
```php
// In Form_Validator.php, add to $form_requirements:
99 => array(
    'name' => 'New Form Name',
    'required_fields' => array( 'field_xxx', 'field_yyy' ),
    'conditional' => true,
    'conditional_fields' => array(
        'field_child' => array( 'parent' => 'field_parent', 'value' => 'Ja' ),
    ),
),
```

## Benefits

✅ **Dynamic Form Loading** - Only show relevant forms per product
✅ **Flexible Validation** - XML-based rules that can be configured
✅ **Error Handling** - Clear validation errors shown to users
✅ **Conditional Logic** - Support for parent-child field dependencies
✅ **Maintainable** - Centralized configuration in Form_Validator
✅ **Scalable** - Easy to add new products and form combinations

## Next Steps

1. Test with actual product selections
2. Configure product meta/attributes to use the mapping
3. Add admin UI for managing form-product relationships
4. Extend validation with pattern matching (phone, email, postcode)
