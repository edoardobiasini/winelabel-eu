# WineLabel EU — Go-to-Market Checklist

## Phase 1: Foundation

### Technical
- [ ] DNS: Point winelabel.net to Cloudflare Pages
- [ ] Deploy `landing/` to Cloudflare Pages (`npx wrangler pages deploy landing/ --project-name=winelabel-eu`)
- [ ] LemonSqueezy: Complete verification → create products (Pro yearly €49, lifetime €129)
- [ ] Update landing page buttons with real LemonSqueezy checkout URLs + GitHub release ZIP URL
- [ ] Create first GitHub release: `git tag v1.0.0 && git push origin v1.0.0` (workflow auto-builds ZIP)
- [ ] Test auto-update flow end-to-end (install free → activate Pro → check for updates)
- [ ] Set up info@winelabel.net email (Aruba webmail)
- [ ] Create OG image (1200x630px) for social sharing → `landing/og-image.png`

### Content
- [ ] Record 2-minute screencast: install → add wine → scan QR → see label
- [ ] Take 3-4 screenshots (admin UI, frontend label, QR PDF, settings page)
- [ ] Upload screenshots to landing page

## Phase 2: Organic Channels

### Week 3 — Direct Outreach
- [ ] Compile list of 50 Italian wineries using WordPress (check with BuiltWith/Wappalyzer)
- [ ] Send personalized emails using template in `landing/assets/outreach-template-it.md`
- [ ] Post on LinkedIn (personal, "I built this" angle)
- [ ] Post on Reddit: r/WordPress, r/winemaking, r/WooCommerce

### Week 4 — WordPress Ecosystem
- [ ] Submit to WPBeginner, WPMayor, ManageWP for review
- [ ] Apply to WooCommerce marketplace partner program
- [ ] Write Dev.to article: "How I built an EU-compliant digital wine label plugin"

### Ongoing — SEO
- [ ] Blog post live: `/blog/eu-wine-label-regulation-2025.html` ✅
- [ ] Write 2nd post: "How to create QR codes for wine bottles"
- [ ] Write 3rd post: "Etichetta digitale vino: guida completa" (Italian)
- [ ] Submit sitemap to Google Search Console

### Consider Later
- [ ] WordPress.org lite version (strip Composer deps — no QR PDF, no dompdf)
- [ ] Italian wine associations: Coldiretti, Confagricoltura, Federvini
- [ ] Wine trade press: Gambero Rosso, Decanter tech section

## Phase 3: Paid (after organic validation)
- [ ] Google Ads: "EU wine label regulation", "etichetta digitale vino" — €5-10/day
- [ ] Facebook/Instagram: target winery owners in IT, FR, ES, DE
