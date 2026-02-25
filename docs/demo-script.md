# Demo script & test plan (interview)

> **Note:** All required environment variables for Laravel, Node WhatsApp service, and Ollama are provided in `.env.example`. Copy it to `.env` and fill your own values before running the project.

## Happy path demo


## Setup & Running the Project

To set up and run the project locally, follow these steps:

git clone https://github.com/azmy-developer/whatsapp-project.git
cd d:\xampp\htdocs\whatsapp-project
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan queue:work



1. **Login to Filament panel**
   - URL: `/admin`
   - Login with an Admin user.

2. **Create a WhatsApp account record**
   - Menu: `WhatsApp Accounts`
   - Click **Create** and fill:
     - Label: `Main support line`
     - Phone: your WhatsApp number.

3. **Connect WhatsApp via QR**
   - On the WhatsApp accounts table, click **Connect / Show QR**.
   - A modal opens with a QR image.
   - On your phone, open WhatsApp → Linked devices → Scan the QR.
   - Status field should become `connected` (after Node webhook updates).

4. **Sync conversations**
   - Click **Sync conversations** on the same row.
   - Queue job `SyncConversationsJob` runs and pulls chats into the `conversations` table.

5. **Browse conversations**
   - Menu: `Conversations`
   - Verify list of chats, last message time, and phone.

6. **Create / link a customer**
   - Menu: `Customers` → **Create**.
   - Fill name + phone.
   - Go back to `Conversations`, choose a chat and click **Link customer**.
   - Select the created customer and confirm.

7. **Generate AI summary**
   - On the conversation row, click **Generate summary**.
   - This triggers:
     - `SyncMessagesJob` to pull (up to) the last 50 messages.
     - `GenerateCustomerSummaryJob` to call Ollama and store a `CustomerSummary`.
   - After a few seconds, open the linked customer and confirm:
     - `AI summary` field is filled.
     - `ai_summary_updated_at` is set.

## Basic test checklist

- Migrations run successfully and all Filament menus appear.
- `WhatsApp Accounts`:
  - Can create/edit records.
  - `Connect / Show QR` works when the Node service is up.
- `Conversations`:
  - Sync job populates data.
  - Filters for linked / unlinked conversations behave correctly.
  - `Link customer` updates `customer_id`.
- `Customers`:
  - Relation manager shows linked conversations.
  - AI summary field is read-only.
- Ollama:
  - When running, summaries are generated.
  - When Ollama is down, a fallback message appears (no crash).

