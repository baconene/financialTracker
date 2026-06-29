# Claude Code Instructions

## Git Push Policy

After every commit, always push to **both** branches:

```bash
git push -u origin claude/personal-finance-app-lhbhdr
git push origin claude/personal-finance-app-lhbhdr:main
```

Never push to only the feature branch — always sync main as well.
