# LMSWireguard
# autor Jarosław Kłopotek <jkl@interduo.pl>

#Generator tuneli Wireguard z poziomu WWW

Wymaga paczek:
- php-radius,
- phpqrcode,

Instalacja:
- tworzymy site w apache2,
- wrzucamy pliki,
- konfigurujemy zmiennel z pliku: wg_config.php,

Skrypt:
1. Tworzy konfigi dla tuneli wireguard
2. W LMS dodaje komputer u wybranego
3. Dodaje zobowiązanie
4. Robi przeładowanie
5. Wgrywa konfig na koncentrator tuneli Mikrotik
6. Generuje kod QR do zaczytania urządzeniami mobilnymi.

Ma wsparcie dla duplikatów i rozróżnia użytkowników "operatorów" (dając im dostęp do dodatkowego VLANu).

