# LMSWireguard
##### (re)Generator konfiguracji tuneli wireguard dla koncentratora Mikrotik z poziomu WWW
##### autor: Jarosław Kłopotek <jkl@interduo.pl>

Wymaga paczek:
- php-radius,

Co robi skrypt:
1. Tworzy konfigi dla tuneli wireguard
2. W LMS dodaje komputer u zdefiniowanego klienta
3. Dodaje zobowiązanie
4. Wgrywa konfig na koncentrator tuneli Mikrotik
5. Wykonuje przeładowanie kolejkowania

Zaimplementowane funkcje:
- logowanie po Radiusie jako autoryzacja,
- możliwość ponownego podejrzenia konfiga,
- generator kodów QR do zaczytania konfiguracji tunelu na urządzenia mobile,
- rozróżnia operatorów (dając im dostęp do specyficznego VLANu),
- możliwość wygenerowania dwóch konfiguracji dla użytkownika,
- możliwość wygenerowania konfiguracji tunelu z routingiem tylko do zdefiniowanych podsieci/intranet przez tunel,

Instalacja (dla apache2):
- git clone https://github.com/interduo/LMSWireguard /opt/LMSWireguard
- cd /opt/LMSWireguard/priv
- composer install
- cp ./apache2-vhost-wireguard.conf /etc/apache2/sites-available/
(skopiowany plik należy dopasować do swoich potrzeb)
- a2ensite apache2-vhost-wireguard.conf,
- LMS: tworzymy oddzielną sieć dla tuneli wireguard (np.172.20.20.0/24),
- LMS: tworzymy oddzielną taryfę dla tuneli wireguard,
- edytujemy stałe w pliku: priv/wg_config.php,
- systemctl restart apache2,
- na koncentratorze tuneli Mikrotik

/interface wireguard add listen-port=13231 mtu=1420 name=wg0 \
/ip firewall filter add action=accept chain=input comment="Allow Wireguard from All" dst-port=13231 protocol=udp \
/ip address add address=172.20.20.1/24 comment="Wireguard Interface IP address" interface=wg0 network=172.20.20.0 \
(adres IP ma być z utworzonej sieci w LMS)

Na urządzeniu wchodzimy na stronę zdefiniowaną w ServerName (sites-enabled/apache2-vhost-wireguard.conf) w przykladzie http://wireguardlms.domena.pl

Pobieramy klienta tuneli wireguard:
https://www.wireguard.com/install/

1. Logujemy się:\
![image](https://github.com/interduo/LMSWireguard/assets/17087236/cac7dd0b-58b7-42f5-953d-25ade7f43cdc)

\
\
2. Zrzucamy konfig do pliku lub zaczytujemy QRcode urządzeniem mobilnym:\
![image](https://github.com/interduo/LMSWireguard/assets/17087236/29327f13-f564-409c-86f3-ebb13470ffc8)

Po co?
By mieć bezpieczne tunele VPN i nie zajmować się regeneracją konfigów i ich wydawaniem.
