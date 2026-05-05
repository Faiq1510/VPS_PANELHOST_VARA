 VPS Panel — Docker-Based VPS Hosting Panel
![PHP](https://img.shields.io/badge/PHP-8.1-blue?logo=php)
![Apache](https://img.shields.io/badge/Apache-2.4-red?logo=apache)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange?logo=mysql)
![Docker](https://img.shields.io/badge/Docker-29.4-blue?logo=docker)
![License](https://img.shields.io/badge/License-MIT-green)
Panel hosting VPS berbasis Docker yang dibangun dengan PHP Native, MySQL, dan Apache. Panel ini memungkinkan administrator untuk mengelola VPS container Docker melalui antarmuka web yang modern dan responsif, dilengkapi dengan virtual console berbasis browser menggunakan Shellinabox.

Preview

Dashboard → VPS Containers → Terminal → Voucher → Pengaturan

 Fitur Utama
Dashboard Real-time — Monitoring CPU, RAM, Disk, Network, dan Uptime server secara live (auto refresh 10 detik)
Manajemen VPS Container — Buat, start, stop, dan hapus Docker container dengan mudah
Virtual Console — Akses terminal container langsung dari browser via Shellinabox tanpa SSH client
Manajemen User — Tambah, edit, hapus user dengan sistem role (Admin & User)
Sistem Voucher — Generate dan kelola kode voucher sebagai kredit pembuatan VPS
Topup Voucher — Admin bisa menambah saldo voucher ke user tertentu
Terminal Server — Akses terminal server host langsung dari panel
Pengaturan Panel — Konfigurasi nama panel, domain, port range, dan Docker image
Ganti Password — Admin bisa ganti password secara langsung dari panel
Info Sistem — Menampilkan versi PHP, Apache, Docker, uptime, dan resource usage

Instalasi
1. Update Sistem
bash
apt update && apt upgrade -y
apt install -y curl wget git unzip nano ufw

2. Setting Firewall
bash
ufw allow 22/tcp      # SSH
ufw allow 80/tcp      # HTTP
ufw allow 443/tcp     # HTTPS
ufw allow 4200/tcp    # Shellinabox
ufw allow 12000:13000/tcp  # Range SSH container
ufw allow 21000:22000/tcp  # Range Web container
ufw allow 4000:5000/tcp    # Range Console container
ufw enable

3. Install Docker
bash
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
systemctl enable docker
systemctl start docker

4. Install Apache2 & PHP
bash
apt install -y apache2
systemctl enable apache2

apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update

apt install -y php8.1 libapache2-mod-php8.1 php8.1-mysql \
  php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd

a2enmod rewrite proxy proxy_http proxy_wstunnel headers
systemctl restart apache2
```
5. Install MySQL
bash
apt install -y mysql-server
systemctl enable mysql
mysql_secure_installation
```
6. Install Shellinabox
bash
apt install -y shellinabox
pkill shellinaboxd 2>/dev/null
shellinaboxd --no-beep -t -s /:root:root:/:/bin/bash \
  --port=4200 --background --disable-ssl

7. Buat Database
bash
mysql -u root -p

sql
CREATE DATABASE panel_vps CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'panel_user'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON panel_vps.* TO 'panel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

8. Import Struktur Database
sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin','user') DEFAULT 'user',
    voucher_balance INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE containers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    container_name VARCHAR(100) UNIQUE NOT NULL,
    domain VARCHAR(150),
    ssh_port INT UNIQUE NOT NULL,
    web_port INT UNIQUE NOT NULL,
    console_port INT UNIQUE NOT NULL,
    root_password VARCHAR(100),
    status ENUM('running','stopped','deleted') DEFAULT 'running',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    amount INT DEFAULT 1,
    used_by INT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT
);

INSERT INTO settings VALUES
('panel_name','VPS Panel'),
('domain','yourdomain.com'),
('ssh_port_start','12000'),
('web_port_start','21000'),
('console_port_start','4000'),
('docker_image','vps-hosting:latest');

-- Default admin (password: admin123)
INSERT INTO users (username,password,email,role,voucher_balance)
VALUES ('admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin@domain.com','admin',9999);

9. Build Docker Image
bash
mkdir -p /opt/vps-image

cat > /opt/vps-image/Dockerfile << 'EOF'
FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y \
    openssh-server nginx php8.1 php8.1-fpm \
    curl wget nano supervisor && apt-get clean
RUN mkdir /var/run/sshd
COPY entrypoint.sh /entrypoint.sh
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod +x /entrypoint.sh
EXPOSE 22 80
ENTRYPOINT ["/entrypoint.sh"]
EOF

cat > /opt/vps-image/entrypoint.sh << 'EOF'
#!/bin/bash
[ ! -z "$ROOT_PASSWORD" ] && echo "root:$ROOT_PASSWORD" | chpasswd
ssh-keygen -A
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOF

cat > /opt/vps-image/supervisord.conf << 'EOF'
[supervisord]
nodaemon=true
[program:sshd]
command=/usr/sbin/sshd -D
autostart=true
autorestart=true
[program:nginx]
command=/usr/sbin/nginx -g "daemon off;"
autostart=true
autorestart=true
EOF

chmod +x /opt/vps-image/entrypoint.sh
cd /opt/vps-image
docker build -t vps-hosting:latest .

10. Deploy Panel
bash
# Clone repository
git clone https://github.com/Faiq1510/VPS_PANELHOST_VARA.git /var/www/html/vps-panel

# Konfigurasi database
cp /var/www/html/vps-panel/config/database.example.php \
   /var/www/html/vps-panel/config/database.php

# Edit sesuai kredensial database kamu
nano /var/www/html/vps-panel/config/database.php

# Set permission
chown -R www-data:www-data /var/www/html/vps-panel
chmod -R 755 /var/www/html/vps-panel
usermod -aG docker www-data
systemctl restart apache2

11. Konfigurasi Apache Virtual Host
bash
cat > /etc/apache2/sites-available/vps-panel.conf << 'EOF'
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>

    ProxyRequests Off
    ProxyPreserveHost On
    ProxyPass /shellinabox/ http://localhost:4200/
    ProxyPassReverse /shellinabox/ http://localhost:4200/
</VirtualHost>
EOF

a2ensite vps-panel.conf
a2dissite 000-default.conf
systemctl reload apache2

12. Akses Panel
Buka browser dan akses:

http://yourdomain.com/vps-panel/

Login dengan:

Username : admin
Password : admin123

> Segera ganti password default setelah login pertama!

Developer
Dibuat oleh Faiq — GitHub @Faiq1510

License
MIT License — bebas digunakan dan dimodifikasi.
