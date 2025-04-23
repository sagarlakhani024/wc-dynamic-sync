# WooCommerce Dynamic Sync Plugin

A powerful WooCommerce extension that provides a custom REST API endpoint to dynamically create or update products and orders from an external system.

---

## 📌 Features

- ✅ Custom REST endpoint: `POST /wc-dynamic/v1/sync`
- ✅ Create or update simple products via SKU
- ✅ Apply automatic 10% discount using sale price
- ✅ Create users if not registered and email them login credentials
- ✅ Create orders with calculated shipping
- ✅ Automatically handle stock and order totals

---

## 🔧 Installation

### 1. Prerequisites
- WordPress version **6.0+**
- WooCommerce plugin must be **installed and activated**
- PHP version **7.4 or higher**

### 2. Installation Steps

**Option A: Upload via WordPress Dashboard**
1. Go to **Plugins > Add New > Upload Plugin**
2. Upload the plugin ZIP file
3. Click **Install Now** and then **Activate**

**Option B: Manual Upload**
1. Extract the plugin ZIP file
2. Upload the extracted folder to `/wp-content/plugins/` via FTP/SFTP
3. Go to **Plugins** in WordPress Admin
4. Click **Activate** on *WooCommerce Dynamic Sync*

---

## 📦 API Endpoint

**POST** `/wp-json/wc-dynamic/v1/sync`

### Sample JSON Payload

```json
{
  "products": [
    {
      "sku": "TSHIRT-001",
      "title": "Premium Cotton T-Shirt",
      "description": "High-quality unisex t-shirt.",
      "price": 25.00,
      "stock_quantity": 50,
      "weight": 0.3
    }
  ],
  "order": {
    "user": {
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "billing": {
        "address_1": "123 Main St",
        "city": "New York",
        "country": "US"
      }
    },
    "shipping": {
      "address_1": "123 Main St",
      "city": "New York",
      "country": "US"
    }
  }
}
