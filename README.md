# Unlockwithus.com

IMEI / iCloud checking service, built on PHP 8 + Apache (Docker) + PostgreSQL.
Designed to deploy on GitHub + Render.com.

## What this includes

- Public site: Home, Register, Login (EN / RU)
- User dashboard: balance, place orders (IMEI Carrier Check / iCloud ON-OFF Check),
  full order history, full balance/funding history
- Admin panel: view all users + their full history, manually add/remove funds
  (logged as transactions the user can see), reset a user's password, suspend/
  reactivate accounts, add/edit/delete services and prices, site-wide order log
- Automatic refund if the sickw.com API call fails
- sickw.com API key stored as an environment variable — **never in code, never
  committed to GitHub**

## 1. Push this to GitHub

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/unlockwithus.git
git push -u origin main
```

The `.gitignore` already excludes `.env` — only `.env.example` (with no real
secrets) gets committed, so it's safe to make this repo public if you want.

## 2. Create a PostgreSQL database on Render

1. Render dashboard → **New** → **PostgreSQL**
2. Give it a name (e.g. `unlockwithus-db`), choose a plan, create it
3. Once created, open it and copy the **Internal Database URL**
   (looks like `postgres://user:pass@host/dbname`)

## 3. Create the Web Service on Render

1. Render dashboard → **New** → **Web Service**
2. Connect your GitHub repo
3. Render will detect the `Dockerfile` automatically — choose **Docker** as
   the environment (no build/start command needed, the Dockerfile handles it)
4. Under **Environment**, add these variables:

   | Key | Value |
   |---|---|
   | `DATABASE_URL` | paste the Internal Database URL from step 2 |
   | `SICKW_API_KEY` | your real sickw.com API key |
   | `ADMIN_EMAIL` | the email you want to use to log into the admin panel |
   | `ADMIN_PASSWORD` | a strong password for that admin account |
   | `APP_SECRET` | any long random string |

5. Deploy. Render will build the Docker image and start the container.

The very first time the app runs, it automatically:
- Creates all database tables
- Seeds the two default services (iPhone Carrier Check $0.60, iCloud ON/OFF
  Check $0.20)
- Creates your admin account using `ADMIN_EMAIL` / `ADMIN_PASSWORD`

You can log in immediately at `/login.php` with those admin credentials.

## 4. Connecting your domain (unlockwithus.com) — do this whenever you're ready

This step is completely independent of everything above — you can test the
site on the free `*.onrender.com` URL Render gives you for as long as you
like before touching your domain or your existing site.

1. Render → your Web Service → **Settings** → **Custom Domains** → add
   `unlockwithus.com` (and `www.unlockwithus.com` if you want both)
2. Render shows you a DNS record to add (usually a CNAME, or an A record for
   the root domain) — add it at wherever your domain's DNS is managed
3. Wait for DNS to propagate (often under an hour) and Render to issue the
   SSL certificate automatically

## About the API key safety

The code reads the key like this:

```php
define('SICKW_API_KEY', getenv('SICKW_API_KEY') ?: '');
```

It is never written in any file. The real value only exists inside Render's
dashboard (Environment Variables), which only you can see. This is the same
approach used by real production applications.

## Notes / what to do next

- **Email verification & automatic password reset**: not included yet, as
  agreed. The code is structured so this can be added later without
  reworking the database (a `password_resets` table + a mail provider like
  Resend/SendGrid would be added).
- **Automatic top-ups (real payments)**: currently funding is manual — you
  add balance from the admin panel after the user contacts you. This can be
  replaced later with a payment gateway.
- **First login**: go to `/login.php`, log in with `ADMIN_EMAIL` /
  `ADMIN_PASSWORD`, you'll land in `/admin/index.php`.
