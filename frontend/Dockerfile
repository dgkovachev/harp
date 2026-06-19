
# Stage 1: Build with Node.js (Alpine for smaller size)
FROM node:20-alpine AS builder

WORKDIR /app

RUN apk add --no-cache nodejs npm
# Copy dependency files first (for caching)
COPY package.json package-lock.json ./
RUN npm ci --only=production || npm install

# Copy source code
COPY . .

# Stage 2: Serve with Nginx
FROM nginx:alpine

# Copy built files from builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]