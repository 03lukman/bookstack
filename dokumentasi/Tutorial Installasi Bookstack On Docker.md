
# Persiapan Awal
Pastikan anda sudah mempersiapkan beberpa bahan berikut :
- Ubuntu Server 24.04.03 LTS Sebagai system operasi linux
- Docker Engine & Docker Compose 
- Docker Bookstack
- Docker Caddy sebagai reverse proxy

### 1. Langkah install docker di Ubuntu 24.04.03 LTS
Hapus terlebih dahulu paket yang memungkinkan untuk untuk menghindari konflik dengan versi yang disertakan dalam Docker Engine
 

```
sudo apt remove $(dpkg --get-selections docker.io docker-compose docker-compose-v2 docker-doc podman-docker containerd runc | cut -f1)
```

Install menggunakan apt repository
`
```
# Add Docker's official GPG key:
sudo apt update
sudo apt install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:
sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
```

Install Paket Dockernya

```
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

Setelah install paket dockernya kemungkinan ada beberapa yang service yang belum running, jalankan perintah ini 

```
sudo docker run hello-world
```

### 2. Installasi Docker Bookstack
Buat terlebih dahulu folder docker untuk bookstack dengan perintah

```
mkdir docker && cd docker 
```

Untuk saat ini posisi anda sudah ada di directory Docker, lanjut untuk menjalankan perintah untuk mengcloning bookstack 

```
git clone [https://github.com/03lukman/bookstack.git
```

Folder docker Bookstack sudah ada di linux anda. untuk memastikan tinggal ketikan perintah   ls -l untuk memastikan folder bookstack sudah ada di dalam folder docker

Lanjut masuk ke dalam folder bookstack dengan perintah 

```
cd bookstack
```

Kemudian Rename file .env-example menjadi.env dengan perintah:

```
mv .env-example .env
```

edit isi file .env dengan perintah nano, seperti berikut

```
nano .env
# --- DATABASE CONFIG ---
DB_ROOT_PASSWORD=your_root_password_here
DB_NAME=bookstack(contoh)
DB_USER=bookstack(contoh)
DB_PASSWORD=your_db_password_here

# --- APP CONFIG ---
# Ganti dengan domain perusahaan Anda
APP_URL=https://your-bookstack-domain.com
TZ=Asia/Jakarta (Lokasi waktu anda)

# --- OIDC KEYCLOAK CONFIG ---
OIDC_NAME="Company Login"
OIDC_CLIENT_ID=bookstack
OIDC_CLIENT_SECRET=your_client_secret_here
OIDC_ISSUER=https://your-keycloak-domain.com/realms/your-realm
OIDC_LOGOUT_ENDPOINT=https://your-keycloak-domain.com/realms/your-realm/protocol/openid-connect/logout
```
untuk menyimpan tekan tombol ctrl + x , kemudian tekan ctrl + y, kemudian tekan tombol enter


Isi variabel berikut:
- DB_PASSWORD: Buat password database yang kuat.
- APP_URL: Masukkan domain
- OIDC_CLIENT_SECRET: ibuat dari keycloak

### 3. Cara mendapatkan client secret dari keycloak
Masuk kedalam web keycloak dengan login menggunakan browser 

```
https://login.ad.agson.co.id/
```

- Untuk akun keycloak silahkan menghubungi admin atau administrator agar bisa dibuatkan akses login

Saat sudah login, masuk ke manage realms, dan pilih agson

![[keycloak_realms.png]]

Kemudian, klik menu clients dan create client (lihat tampilan gambar di bawah)

![[keycloak_client.png]]

Untuk general setting, silahkan isi dengan
- Client type: Silahkan gunakan OpenID Connect
- Untuk Client ID, nama dan deskripsi silahkan disesuaikan dengan selera anda, kemudian klik tombol Next

Kemudian, untuk tahap selanjutnya adalah setting Capability config, dengan mengaktifkan:
- Client authentication menjadi status ON
- Authentication flow ( checklist di bagian Standard flow & Direct access grants), kemudian klik tombol Next 
 
![[keycloak_config.png]]

Selanjutnya, tahapan login setting:
- Root URL : isi dengan root URL aplikasi yang sudah di daftarkan di docker caddy

kemudian klik tombol Next 

![[keycloak_login.png]]

Setelah clients berhasil dibuat, selanjutnya klik menu credentials, dan copy kode client secret dan masukan kedalam file .env

![[keycloak_secret.png]]

### Setting Caddy (Reverse Proxy)
Buat dahulu folder docker caddynya. Karena sebelumnya kita sudah buat folder docker untuk installasi bookstack, maka kita akan gunakan folder docker tersebut untuk installasi docker caddy

```
cd ~
cd docker
mkdir caddy
cd caddy
```

Selanjutnya buat docker compose untuk caddy seperti berikut:

```
cat <<EOF > docker-compose.yml
services:
  caddy:
    image: iarekylew00t/caddy-cloudflare:latest
    restart: unless-stopped
    ports:
      - 80:80
      - 443:443
      - 443:443/udp
    volumes:
      - ./caddy:/etc/caddy
      - ./data:/data
    networks:
      - net-caddy

networks:
  net-caddy:
    external: true
EOF
```

Ubah settingan networks dan sesuaikan dengan konfigurasi anda atau membuat network baru:

```
docker network ls
docker network create net-caddy
```
Untuk nama networks, sesuaikan juga dengan yang ada pada docker-compose.yml

Setelah itu, silahkan buat folder baru lagi di dalam caddy, dan buat file Caddyfile dalam directory caddy tersebut:

```
mkdir caddy
cd caddy
nano Caddyfile #kemudian tekan enter
```

Isi Caddyfile 

```
book.domain.anda.co.id {
        reverse_proxy bookstack:8080
}
```
untuk menyimpan tekan tombol ctrl + x , kemudian tekan ctrl + y, kemudian tekan tombol enter


## Setting alias DNS server atau menambahkan CNAME record di server manager

Berikut langkah tutorialnya bisa simak atau pelajari dari link website ini :

```
https://www.server-world.info/en/note?os=Windows_Server_2012&p=dns&f=6
```

Selanjutnya, jalankan program docker bookstack dan docker caddy secara bersamaan dengan menjalankan perintah:

```
cd ~
cd docker
docker compose -f book/docker-compose.yml up && docker compose -f crm/docker-compose.yml up
```

Setelah docker bookstack dan docker caddy berhasil dijalankan, silahkan buka browser untuk mengakses bookstack menggunakan chrome atau yang lainnya, kemudian masuk ke domain bookstack anda yang sudah dibuat. sepertu contoh:

```
https://book.ad.agson.co.id/
```

Jika instalasi berhasil, maka akan menampilkan halaman seperti pada gambar berikut:

![[dashboard_login.png]]

Silahkan anda klik tombol Login , dan masukan username dan password sesuai dengan akun active directory user account anda.

![[keycloak_loginagson.png]] 

Kemudian, jika berhasil maka akan menampilkan dashboard aplikasi bookstack, dan ketika muncul notifikasi pop-up seperti pada gambar dibawah *"You do not have to access the requested page"*, maka silahkan hubungi admin atau administrator untuk meminta akses permission aplikasi.

![[gambar/bookstack&keycloack terhubung.png]] 

Dengan ini, maka aplikasi bookstack telah berhasil terinstall pada komputer anda. Jika anda memiliki error pada langkah-langkah diatas, silahkan hubungi tim admin anda. Terimakasih