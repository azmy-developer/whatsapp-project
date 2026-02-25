
## Setup & Running the Project

To set up and run the project locally, follow these steps:

git clone https://github.com/azmy-developer/whatsapp-node-service.git
cd d:\xampp\htdocs\whatsapp-node-service
npm install
node index.js


# WhatsApp Web Node Service (Design)

This service runs `whatsapp-web.js` and exposes a minimal HTTP API for Laravel.

## Endpoints

- `POST /sessions/start`
  - Body: `{ "account_id": number, "session_ref": string|null, "webhook_secret": string|null }`
  - Creates or resumes a WhatsApp Web session and returns:
    - `{ "session_ref": "string", "status": "waiting_for_qr" | "connected" }`

- `POST /sessions/stop`
  - Body: `{ "session_ref": "string" }`
  - Logs out and destroys the underlying WhatsApp Web session.

- `GET /sessions/:session_ref/qr`
  - Returns `{ "qr": "data" }` while waiting for login, or HTTP `204` when not needed.

- `GET /conversations`
  - Query: `session_ref`
  - Returns an array of conversations:
    - `{ "id": "provider-chat-id", "name": "Contact name", "phone": "E164", "last_message_at": "ISO8601" }[]`

- `GET /conversations/:chatId/messages`
  - Query: `session_ref`, `limit`
  - Returns an array of messages:
    - `{ "id": "provider-message-id", "direction": "inbound"|"outbound", "body": "text", "sent_at": "ISO8601" }[]`

## Webhooks to Laravel

The service sends signed webhooks to Laravel:

- `POST /api/webhooks/whatsapp/message`
  - Body: `{ "session_ref": "string", "chat": {...}, "message": {...} }`

- `POST /api/webhooks/whatsapp/status`
  - Body: `{ "session_ref": "string", "status": "connected"|"disconnected"|"error", "error": "string|null" }`

The service includes header `X-Webhook-Secret` which must match `services.whatsapp_node.webhook_secret`.

