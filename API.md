# NOS API (Namma Ooru Service)

REST API for the NOS Android app. Base URL: `/api/v1`.

## Endpoints

### 1. Send OTP

**POST** `/api/v1/send-otp`

Send OTP to the given phone (e.g. via WhatsApp). In production, integrate with Twilio/WhatsApp Business API.

**Request body:**

```json
{
  "phone": "+919876543210",
  "channel": "whatsapp"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "OTP sent successfully"
}
```

---

### 2. Verify OTP

**POST** `/api/v1/verify-otp`

Verify OTP for the given phone.

**Request body:**

```json
{
  "phone": "+919876543210",
  "otp": "123456"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "OTP verified successfully"
}
```

**Error (422):** Invalid or expired OTP.

---

### 3. Submit Sales Lead (Product Order)

**POST** `/api/v1/sales-lead`

Create a new sales lead (product order) from the app.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| orderId | string | yes | Unique order ID |
| productId | string | yes | Product ID |
| productName | string | yes | Product name |
| variantId | string | no | Selected variant ID |
| variantName | string | no | Variant name |
| variantSpecifications | object | no | Variant specs (key-value) |
| quantity | integer | yes | Quantity (min 1) |
| unitPrice | number | yes | Price per unit |
| totalAmount | number | yes | Total amount |
| customerName | string | no | Customer name |
| phone | string | no | Customer phone |
| email | string | no | Customer email |
| address | string | no | Full address |
| street | string | no | Street |
| city | string | no | City/District |
| district | string | no | District |
| state | string | no | State |
| pincode | string | no | Pincode |
| paymentMethod | string | no | e.g. "Cash on Delivery" |
| date | string | no | ISO date/time |
| productDetails | object | no | Extra product info |

**Response (201):**

```json
{
  "success": true,
  "message": "Order placed successfully",
  "data": {
    "id": 1,
    "orderId": "ORD-12345"
  }
}
```

**Error (422):** Validation error or duplicate orderId.

---

### 4. Submit Service Lead (Service Booking)

**POST** `/api/v1/service-lead`

Create a new service booking from the app.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| bookingId | string | yes | Unique booking ID |
| serviceId | string | yes | Service ID |
| serviceName | string | yes | Service name |
| serviceTypeId | string | no | Service type ID |
| serviceTypeName | string | no | Service type name |
| serviceTypeDescription | string | no | Description |
| servicePrice | number | no | Price |
| serviceDuration | string | no | e.g. "2-3 hours" |
| date | string | no | ISO date |
| timeSlot | string | no | e.g. "10:00 AM" |
| address | string | no | Full address |
| street | string | no | Street |
| city | string | no | City/District |
| district | string | no | District |
| state | string | no | State |
| pincode | string | no | Pincode |
| customerName | string | no | Customer name |
| phone | string | no | Customer phone |
| email | string | no | Customer email |
| coordinates | object | no | `{ latitude, longitude }` |

**Response (201):**

```json
{
  "success": true,
  "message": "Booking confirmed successfully",
  "data": {
    "id": 1,
    "bookingId": "BK-12345"
  }
}
```

**Error (422):** Validation error or duplicate bookingId.

---

## Admin API (NOS Master Web)

Base URL: `/api/v1/admin`. Used by the admin dashboard. All endpoints except login require a Bearer token (see Admin login below).

### 5. Admin login

**POST** `/api/v1/admin/login`

Authenticate with email and password. Returns a Sanctum token to use in the `Authorization: Bearer <token>` header for other admin endpoints.

**Request body:**

```json
{
  "email": "admin@nammaooru.com",
  "password": "Admin@123"
}
```

**Response (200):**

```json
{
  "success": true,
  "message": "Logged in successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin",
      "email": "admin@nammaooru.com"
    },
    "token": "1|abc...",
    "tokenType": "Bearer"
  }
}
```

**Error (422):** Invalid credentials (e.g. `{"message":"The given data was invalid.","errors":{"email":["The provided credentials are incorrect."]}}`).

---

### 6. Admin logout

**POST** `/api/v1/admin/logout`

Revokes the current token. Requires `Authorization: Bearer <token>`.

**Response (200):** `{"success":true,"message":"Logged out successfully"}`.

---

### 7. Current admin user

**GET** `/api/v1/admin/me`

Returns the authenticated admin user. Requires `Authorization: Bearer <token>`.

**Response (200):** `{"success":true,"data":{"user":{"id":1,"name":"Admin","email":"admin@nammaooru.com"}}}`.

---

### 8. Dashboard metrics

**GET** `/api/v1/admin/dashboard`

Returns counts for sales and service leads by status.

**Response (200):**

```json
{
  "success": true,
  "data": {
    "salesLeads": {
      "total": 156,
      "yetToStart": 45,
      "inProgress": 78,
      "completed": 33
    },
    "serviceLeads": {
      "total": 203,
      "yetToStart": 67,
      "inProgress": 92,
      "completed": 44
    }
  }
}
```

---

### 9. List sales leads

**GET** `/api/v1/admin/sales-leads`

Query params: `search`, `status` (Yet to start | In progress | Completed), `date_from`, `date_to` (YYYY-MM-DD), `per_page` (default 20, max 100), `page`.

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "orderId": "ORD-12345",
      "customerName": "John Doe",
      "phone": "+919876543210",
      "email": "john@example.com",
      "product": "AC Unit",
      "productId": "prod-1",
      "quantity": 1,
      "unitPrice": 15000,
      "totalAmount": 15000,
      "status": "Yet to start",
      "assignedTo": "1",
      "createdAt": "2024-01-15T10:00:00.000000Z",
      "address": "...",
      "city": "Chennai",
      "district": "...",
      "state": "Tamil Nadu",
      "pincode": "600001",
      "paymentMethod": "Cash on Delivery",
      "orderDate": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
  }
}
```

---

### 10. Create sales lead

**POST** `/api/v1/admin/sales-leads`

**Request body (JSON):** `customerName` (required), `phone` (required), `email` (required), `product` (required), `status` (optional: "Yet to start" | "In progress" | "Completed"), `assignedTo` (optional).

**Response (201):** Created sales lead object (same shape as list item). A unique `orderId` is generated (e.g. `ADMIN-xxxxxxxx-timestamp`). `quantity`, `unitPrice`, `totalAmount` are set to 1, 0, 0 for manual entries.

---

### 11. Get single sales lead

**GET** `/api/v1/admin/sales-leads/{id}`

**Response (200):** Same object shape as one item in the list above. **404** if not found.

---

### 12. Update sales lead

**PATCH** `/api/v1/admin/sales-leads/{id}`

**Request body:**

| Field       | Type   | Description                                      |
|------------|--------|--------------------------------------------------|
| status     | string | Optional. "Yet to start", "In progress", "Completed" |
| assignedTo | string | Optional. Staff ID or name                       |

**Response (200):** Updated lead object. **404** if not found.

---

### 12. List service leads

**GET** `/api/v1/admin/service-leads`

Query params: same as sales leads (`search`, `status`, `date_from`, `date_to`, `per_page`, `page`).

**Response (200):** Same structure as sales leads list; each item has `bookingId`, `service`, `serviceId`, `servicePrice`, `serviceDuration`, `bookingDate`, `timeSlot`, etc.

---

### 13. Create service lead

**POST** `/api/v1/admin/service-leads`

**Request body (JSON):** `customerName` (required), `phone` (required), `email` (required), `service` (required), `status` (optional: "Yet to start" | "In progress" | "Completed"), `assignedTo` (optional).

**Response (201):** Created service lead object (same shape as list item). A unique `bookingId` is generated (e.g. `ADMIN-xxxxxxxx-timestamp`).

---

### 14. Get single service lead

**GET** `/api/v1/admin/service-leads/{id}`

**Response (200):** Single service lead object. **404** if not found.

---

### 15. Update service lead

**PATCH** `/api/v1/admin/service-leads/{id}`

**Request body:** Same as sales lead update (`status`, `assignedTo`).

**Response (200):** Updated service lead object. **404** if not found.

---

### 16. List staffs

**GET** `/api/v1/admin/staffs`

Query params: `search`, `role`, `date_from`, `date_to` (YYYY-MM-DD), `per_page`, `page`.

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Rajesh Kumar",
      "email": "rajesh@example.com",
      "phone": "+91 9876543210",
      "role": "AC Mechanic",
      "address": "Bangalore",
      "createdAt": "2024-01-15T10:00:00.000000Z",
      "updatedAt": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

---

### 16. Get single staff

**GET** `/api/v1/admin/staffs/{id}`

**Response (200):** Single staff object. **404** if not found.

---

### 17. Create staff

**POST** `/api/v1/admin/staffs`

**Request body:** `name` (required), `email` (required, unique), `phone`, `role`, `address`.

**Response (201):** Created staff object. **422** if validation error (e.g. duplicate email).

---

### 18. Update staff

**PATCH** `/api/v1/admin/staffs/{id}`

**Request body:** Same as create (all optional; `email` must remain unique).

**Response (200):** Updated staff object. **404** if not found.

---

### 19. Delete staff

**DELETE** `/api/v1/admin/staffs/{id}`

**Response (200):** `{"success":true,"message":"Staff deleted successfully"}`. **404** if not found.

---

### 20. List places

**GET** `/api/v1/admin/places`

Query params: `search`, `is_active` (1/0 or true/false), `date_from`, `date_to` (YYYY-MM-DD), `per_page`, `page`.

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Bangalore Central",
      "address": "MG Road, Bangalore",
      "isActive": true,
      "services": [1, 2],
      "products": [1],
      "createdAt": "2024-01-15T10:00:00.000000Z",
      "updatedAt": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

---

### 21. Get single place

**GET** `/api/v1/admin/places/{id}`

**Response (200):** Single place object. **404** if not found.

---

### 22. Create place

**POST** `/api/v1/admin/places`

**Request body:** `name` (required), `address`, `isActive` (boolean), `services` (array of integers), `products` (array of integers).

**Response (201):** Created place object. **422** if validation error.

---

### 23. Update place

**PATCH** `/api/v1/admin/places/{id}`

**Request body:** Same as create (all optional). Use `isActive` to toggle active status.

**Response (200):** Updated place object. **404** if not found.

---

### 24. Delete place

**DELETE** `/api/v1/admin/places/{id}`

**Response (200):** `{"success":true,"message":"Place deleted successfully"}`. **404** if not found.

---

### 25. List services (catalog)

**GET** `/api/v1/admin/services`

Service catalog (Manage Services page), not service leads/bookings. Query params: `search`, `date_from`, `date_to`, `per_page`, `page`.

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "AC Service",
      "description": "Complete AC servicing",
      "serviceTypes": [
        {
          "id": "srv-type-1-xxx",
          "name": "Deep Cleaning",
          "description": "Complete cleaning",
          "price": 499,
          "duration": "2-3 hours"
        }
      ],
      "createdAt": "2024-01-15T10:00:00.000000Z",
      "updatedAt": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

---

### 26. Get single service (catalog)

**GET** `/api/v1/admin/services/{id}`

**Response (200):** Single service object. **404** if not found.

---

### 27. Create service (catalog)

**POST** `/api/v1/admin/services`

**Request body:** `name` (required), `description`, `serviceTypes` (required, array of objects with `name`, `description`, `price`, `duration`). At least one service type required.

**Response (201):** Created service object. **422** if validation error.

---

### 28. Update service (catalog)

**PATCH** `/api/v1/admin/services/{id}`

**Request body:** Same as create (all optional). `serviceTypes` replaces existing types.

**Response (200):** Updated service object. **404** if not found.

---

### 29. Delete service (catalog)

**DELETE** `/api/v1/admin/services/{id}`

**Response (200):** `{"success":true,"message":"Service deleted successfully"}`. **404** if not found.

---

### 30. Upload product image

**POST** `/api/v1/admin/products/upload-image`

Upload a product image. File is stored in **local storage** (`storage/app/product-images`); returns a **signed URL** (valid 7 days) to store in the product. Use the URL as `img src`; the image is served via **GET** `/api/v1/admin/products/image/{path}` (validated by signature). Requires `Authorization: Bearer <token>` for upload.

**Request:** `multipart/form-data` with field `image` (file; jpeg, jpg, png, gif, webp; max 5MB).

**Response (201):**

```json
{
  "success": true,
  "data": { "url": "http://localhost:8000/api/v1/admin/products/image/uuid.jpg?signature=...&expires=..." }
}
```

**Serving images:** No Bearer token is required to load the image URL; the signed query params validate the request. After 7 days the URL expires; re-upload or regenerate signed URLs if needed.

**If upload fails with "File is too large":** PHP’s limits are too low. **Start the server with higher limits** (don’t use plain `php artisan serve`):
- From project root: **`./serve`** (script in repo), or
- **`composer serve`**, or  
- **`php -d upload_max_filesize=8M -d post_max_size=8M artisan serve`**
Then try the upload again.

---

### 31. List products (catalog)

**GET** `/api/v1/admin/products`

Query params: `search`, `date_from`, `date_to`, `per_page`, `page`.

**Response (200):** `{ "success": true, "data": [ { "id", "name", "description", "images": ["url", ...], "variants": [...], "createdAt", "updatedAt" } ], "meta": { ... } }`.

---

### 32. Get single product (catalog)

**GET** `/api/v1/admin/products/{id}`

**Response (200):** Single product object. **404** if not found.

---

### 33. Create product (catalog)

**POST** `/api/v1/admin/products`

**Request body:** `name` (required), `description`, `images` (array of URL strings from upload), `variants` (required, min 1). Each variant: `name`, `price`, `originalPrice`, `specifications` (object), `inStock`, `stockCount`.

**Response (201):** Created product. **422** if validation error.

---

### 34. Update product (catalog)

**PATCH** `/api/v1/admin/products/{id}`

**Request body:** Same as create (all optional). `images` and `variants` replace existing.

**Response (200):** Updated product. **404** if not found.

---

### 35. Delete product (catalog)

**DELETE** `/api/v1/admin/products/{id}`

**Response (200):** `{"success":true,"message":"Product deleted successfully"}`. **404** if not found.

---

## Admin user

After migrations, seed the database to create a default admin user:

```bash
php artisan db:seed
```

This creates **admin@nammaooru.com** with password **Admin@123**. To create more admins, use `php artisan tinker` and then:

```php
\App\Models\User::create([
  'name' => 'Manager',
  'email' => 'manager@nammaooru.com',
  'password' => \Illuminate\Support\Facades\Hash::make('YourPassword'),
]);
```

---

## Running the API

1. Copy `.env.example` to `.env` and set `APP_URL` (e.g. `http://localhost:8000`).
2. Configure database in `.env` (use `DB_CONNECTION=sqlite` and ensure `database/database.sqlite` exists for local dev without MySQL). Run migrations:

   ```bash
   php artisan migrate
   ```

3. Start the server:

   ```bash
   php artisan serve
   ```

4. **Android Emulator:** Use base URL `http://10.0.2.2:8000/api/v1` in the app (already set in NOS_Android_App constants).
5. **Physical device:** Use your machine’s LAN IP, e.g. `http://192.168.1.100:8000/api/v1`, and update `API_BASE_URL` in NOS_Android_App `src/constants/index.ts`.

## Database

- **otp_verifications** – OTPs sent for login (phone, otp, expires_at, verified_at).
- **sales_leads** – Product orders from the app. Includes `assigned_to` (nullable) for admin assignment.
- **service_leads** – Service bookings from the app. Includes `assigned_to` (nullable) for admin assignment.
- **staff** – Staff members (name, email, phone, role, address).
- **places** – Places (name, address, is_active, service_ids JSON, product_ids JSON).
- **services_catalog** – Service catalog (name, description, service_types JSON array of { id, name, description, price, duration }).
- **products_catalog** – Product catalog (name, description, images JSON array of signed URLs, variants JSON). Images are stored in local storage under `storage/app/product-images` and served via signed URLs (see Upload product image).

Lead status values in DB: `pending` (Yet to start), `in_progress`, `completed`. Config in `config/lead.php`.
