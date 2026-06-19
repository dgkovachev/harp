# Frontend

This folder contains a Vite + React app that builds static files into `html/`, which is mounted by the `frontend` nginx container.

## Scripts

- `npm install`
- `npm run dev`
- `npm run build`

## Output

The production build is written to `html/`, so nginx can serve it directly.
