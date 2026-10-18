# Publishing Zelmoriq: GitHub, hosting and search engines

Two different things are involved, and people often mix them up:

| Goal | What makes it work |
|---|---|
| People find your **code** | A GitHub repository with a good name, description, topics and README |
| People find your **website** in Google/Bing | The site must run on a **public domain**; `localhost` can never be indexed |

---

## Part 1: Put the code on GitHub

### 1. Install Git and sign in
1. Install **Git for Windows** from git-scm.com (accept the defaults).
2. Create a free account at github.com.
3. In VS Code open a terminal in the project folder (`E:\XAMPP\htdocs\Zelmoriq`) and tell Git who you are:
   ```
   git config --global user.name "Your Name"
   git config --global user.email "you@example.com"
   ```

### 2. Create the empty repository on GitHub
1. Click **+ → New repository**.
2. **Repository name:** `zelmoriq`
3. **Description:** `College notes-sharing portal in PHP and MySQL: HOD-verified teachers upload, students find, rate and download.`
4. Choose **Public**. Do **not** tick "Add a README", ".gitignore" or "license" (the project already has them).
5. Click **Create repository**.

### 3. Check nothing private is committed
- `config/config.php` and everything inside `uploads/` are already in `.gitignore`.
- If you ever put a real database password in `config.example.php`, remove it first.
- Delete `setup.php` only on your live server; it is safe to keep in the repo.

### 4. Push the code
```
git init
git add .
git status
git commit -m "Initial release: Zelmoriq 1.0.0"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/zelmoriq.git
git push -u origin main
```
Run `git status` before committing and confirm `config/config.php` is **not** listed. On the first push, a browser window asks you to sign in.

### 5. Replace the placeholders
Search the project (`Ctrl+Shift+F`) for `YOUR-USERNAME` and replace it with your GitHub username, in `README.md` and any other file. Commit and push again:
```
git add . && git commit -m "Set GitHub username" && git push
```

### 6. Polish the repository page (this is your GitHub "SEO")
1. **About (gear icon, top right):**
   - Add the description from step 2.
   - Add a website URL once you have one.
   - **Topics:** `php`, `mysql`, `notes-sharing`, `education`, `college-project`, `student-portal`, `xampp`, `pdo`, `agile`, `scrum`, `file-sharing`, `learning-management`
2. **Settings → General → Social preview → Upload an image:** use `assets/og-image.png`. This is the picture shown when the link is shared.
3. **Create a release:** Releases → *Draft a new release* → tag `v1.0.0` → copy the notes from `CHANGELOG.md`.
4. **Enable Issues** and (optional) **Discussions** under Settings → Features.
5. **Replace screenshots:** run the site, take real screenshots, overwrite the files in `docs/screenshots/`, and push.
6. Share the link in college groups, LinkedIn and your CV. Stars and links are what raise a repository in search results.

---

## Part 2: Put the website online

GitHub Pages cannot run PHP, so the site needs a host that supports **PHP 8 + MySQL**.

| Option | Notes |
|---|---|
| Free PHP hosts (e.g. InfinityFree) | Free, good for demos, may be slow and show limits |
| Shared hosting (Hostinger, Namecheap, GoDaddy, etc.) | Cheap, cPanel, easiest for PHP + MySQL |
| A VPS (DigitalOcean, Hetzner, AWS Lightsail) | Most control; you install Apache/Nginx, PHP and MySQL |

### Deploy steps (shared hosting / cPanel)
1. Create a MySQL database and user in cPanel; note the host, name, user and password.
2. Import `database/zelmoriq.sql` with phpMyAdmin. If the host does not allow `CREATE DATABASE`, delete the first two statements (`CREATE DATABASE...` and `USE ...`) from the file before importing into your existing database.
3. Upload the project files to `public_html/` (best: use the **domain root**, not a subfolder, so `robots.txt` works).
4. Copy `config/config.example.php` to `config/config.php` and set:
   ```php
   define('APP_ENV', 'production');
   define('DB_HOST', 'localhost');   // as given by your host
   define('DB_NAME', 'your_db');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'a-strong-password');
   ```
5. Make `uploads/photos`, `uploads/notes`, `uploads/certificates` writable (permission 755, or 775 if uploads fail).
6. Visit `https://your-domain/setup.php`, create the Manager, then **delete `setup.php`**.
7. Turn on **HTTPS** (free Let's Encrypt / "SSL" in cPanel).
8. Remove the demo accounts if you created them.

---

## Part 3: Get found by Google and Bing

The site already includes the technical SEO pieces: title and description tags, canonical links, Open Graph and Twitter cards, JSON-LD structured data, `noindex` on all private pages, and automatic `robots.txt` and `sitemap.xml`.

Once the site is live on your domain:

1. **Check the basics in a browser**
   - `https://your-domain/robots.txt` shows rules that block login, dashboards and uploads.
   - `https://your-domain/sitemap.xml` lists the home, register and roadmap pages.
   - If either shows a 404, your host may have `mod_rewrite` off; then open `robots.php` and `sitemap.php` directly, or copy their output into real `robots.txt` / `sitemap.xml` files.
2. **Google Search Console** (search.google.com/search-console)
   - Add your domain, verify it (HTML tag or DNS record), then go to **Sitemaps** and submit `sitemap.xml`.
   - Use **URL inspection → Request indexing** for the home page.
3. **Bing Webmaster Tools** (bing.com/webmasters): sign in, import from Search Console, submit the same sitemap.
4. **Test the share preview** by pasting your URL into LinkedIn, WhatsApp or Facebook's sharing debugger; you should see the Zelmoriq card from `assets/og-image.png`.
5. **Be patient.** Indexing typically takes a few days to a few weeks. Rankings for a new site depend on content and links.

### Tips that actually help ranking
- Use a short, real domain (for example `zelmoriq.in` or `zelmoriq.com`), and keep it consistent.
- Add real content to the public pages: a short "About", FAQs, and a page per department if you want to rank for college searches.
- Get links: GitHub repo link, college website, LinkedIn post, a short demo video on YouTube.
- Keep pages fast: the CSS and JS are small; compress screenshots before adding more.
- Never remove the `noindex` from login, dashboards, downloads or uploads. Notes and certificates are private and must never be indexed.

### What is deliberately **not** indexed
Everything behind a login (all role pages, profiles, downloads, certificates, uploads), plus `setup.php`, the login page and password-reset pages.
