# LMSWireguard
##### (re)Generator konfiguracji tuneli wireguard dla koncentratora Mikrotik z poziomu WWW
##### autor: Jarosław Kłopotek <jkl@interduo.pl>

Wymaga paczek:
- php-radius,
- phpqrcode,
- wireguard (opcjonalnie),
- wireguard-tools (opcjonalnie),

Co robi skrypt:
1. Tworzy konfigi dla tuneli wireguard
2. W LMS dodaje komputer u wybranego
3. Dodaje zobowiązanie
4. Robi przeładowanie
5. Wgrywa konfig na koncentrator tuneli Mikrotik

Zaimplementowane funkcje:
- logowanie po Radiusie jako autoryzacja,
- możliwość ponownego podejrzenia konfiga,
- generator kodów QR do zaczytania konfiguracji tunelu na urządzenia mobile,
- rozróżnia operatorów (dając im dostęp do specyficznego VLANu),
- tunele można generować z kierowaniem całego ruchu lub wybranych podsieci,

Instalacja (dla apache2):
- git clone https://github.com/interduo/LMSWireguard /opt/LMSWireguard
- kopiujemy pliki lmswireguard.conf, lmswireguard-ssl.conf do /etc/apache2/sites-available/,
- modyfikujemy je wg potrzeb, szczególnie zwracając uwagę na ścieżkę do certyfikatów SSL,
- a2ensite lmswireguard.conf lmswireguard-ssl.conf,
- w LMS tworzymy sieć dla tuneli wireguard,
- w LMS tworzymy taryfę dla tuneli wireguard,
- konfigurujemy zmienne w pliku: wg_config.php,
- systemctl restart apache2,
- na koncentratorze tuneli Mikrotik
/interface wireguard add listen-port=13231 mtu=1420 name=wg0 \
/ip firewall filter add action=accept chain=input comment="Allow Wireguard from All" dst-port=13231 protocol=udp \
/ip address add address=172.20.20.1/24 comment="Wireguard Interface IP address" interface=wg0 network=172.20.20.0 \
(adres IP ma być z utworzonej sieci w LMS i wpisany w configu wg_config.php)

Na urządzeniu wchodzimy na stronę zdefiniowaną w ServerName (sites-enabled/lmswireguard-ssl.conf) w przykladzie wireguard.domena.pl

Pobieramy klienta tuneli wireguard:
https://www.wireguard.com/install/

1. Logujemy się:\
![image](https://github.com/interduo/LMSWireguard/assets/17087236/cac7dd0b-58b7-42f5-953d-25ade7f43cdc)

\
\
2. Zrzucamy QRcode do pliku lub zaczytujemy QRcode urządzeniem mobilnym:\
![image](https://github.com/interduo/LMSWireguard/assets/17087236/29327f13-f564-409c-86f3-ebb13470ffc8)



