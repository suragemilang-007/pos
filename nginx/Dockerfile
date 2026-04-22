FROM nginx:1.25-alpine

# Hapus konfigurasi default nginx
RUN rm /etc/nginx/conf.d/default.conf

# Copy konfigurasi custom
COPY nginx.conf /etc/nginx/conf.d/pos.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
