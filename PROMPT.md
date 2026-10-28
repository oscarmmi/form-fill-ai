Build Form Fill AI in THIS folder: C:\xampp\htdocs\form-fill-ai

A recruiter-ready demo that turns a free-form support message into a filled Harbor Desk intake form.

Laravel 13 + Vue 3 starter kit is already here if present. Reuse it. Do not create a second app inside NewPortfolio.

## Must satisfy

- Models: `IntakeForm` (customer, email, product, urgency low|normal|high, summary, raw_text, valid, errors json).
- `App\Services\Forms\FormFiller` via `LlmClient`. Validate required fields and email format. Timeout 12s. Never 500.
- `FakeLlmClient` in tests and when no `GEMINI_API_KEY`. Optional Gemini when key is set.
- Seed 4 fictional messages (one missing email). UI: paste, filled form fields, validation errors, confirm/save.
- Pest: a complete message fills customer+email+product; missing email is invalid; FakeLlm bound; guest POST 200.
- README mermaid: Message → LLM fill → field validate → form. How to run without API key.

## GitHub

- git config user.name "Oscar Munoz" / user.email "oscarxxi@gmail.com"
- Commit: feat: add form fill ai
- Public https://github.com/oscarmmi/form-fill-ai
- gh repo edit --description "Fill a support intake form from free-form text with an LLM (Laravel + Vue)." --add-topic laravel --add-topic vue --add-topic ai --add-topic php --add-topic nlp
- Stop. Print the URL.

No Impeccable. No Redis. No real customers.
