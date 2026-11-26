# Werken aan OWN Framework

## Branches

- **`main`** is altijd releasebaar: alleen code die werkt en getest is. GitHub
  dwingt af dat er niets op `main` komt zonder pull request en groene CI. Geen
  directe pushes, geen force-push.
- **Werk gebeurt op een korte branch** vanaf `main`, één onderwerp per branch:
  - `feat/…` iets nieuws (wordt een minor-versie)
  - `fix/…` een bugfix (patch-versie)
  - `chore/…` onderhoud, documentatie, tooling (geen eigen release nodig)
- **Pull request naar `main`**, CI moet groen zijn. Mergen met *squash* (één
  nette commit per onderwerp) of *rebase*; merge-commits staan uit. De branch
  verdwijnt na het mergen vanzelf.
- **Tags `v*`** zijn beschermd: niet te verwijderen of te verplaatsen. Sites
  updaten vanaf die tags, dus een uitgebrachte versie verandert nooit meer.

## Lokaal

```bash
pnpm install && composer install
pnpm dev               # thema-CSS/JS bij elke wijziging
pnpm dev:developer     # de ontwikkelaarspagina (developer/src)
pnpm lint:developer
pnpm build             # alles, zoals CI en de release het doen
```

## Een release

1. Zet de wijzigingen onder **Unreleased** in `CHANGELOG.md` (doe dat al in
   elke pull request).
2. Branch `chore/release-x.y.z`: hernoem *Unreleased* naar
   `## [x.y.z] - JJJJ-MM-DD`, zet er een nieuwe lege *Unreleased* boven en
   werk de links onderaan bij.
3. Versie ophogen op twee plekken: `Version:` in `style.css` en `version` in
   `package.json`.
4. Pull request, CI groen, mergen.
5. Op `main`: `git tag -a vX.Y.Z -m "OWN Framework X.Y.Z"` en
   `git push origin vX.Y.Z`.

De release-workflow controleert dat de tag gelijk is aan de versie in
`style.css` en `package.json` én dat `CHANGELOG.md` een sectie voor die versie
heeft, bouwt `own-framework.zip` en zet die sectie als releasetekst op GitHub.
Sites zien de update binnen 12 uur, of meteen via "Opnieuw controleren" op
Netwerkbeheer → Updates.

## Wat je niet breekt

- **Opgeslagen namen** (blocknamen, ACF-velden, post types, capabilities):
  zie "Never rename these" in `CLAUDE.md`. Hernoemen breekt bestaande content.
- **Alleen stabiele `@wordpress/components`** op de ontwikkelaarspagina, geen
  `__experimental*`.
- **De zip bevat de build, niet de bronnen.** Een bestand dat WordPress nodig
  heeft hoort niet in `.distignore`; de release-workflow controleert dat
  `assets/`, `developer/build/` en `vendor/` erin zitten.
