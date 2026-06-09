import ftplib
FTP_HOST = '184.168.114.12'
FTP_USER = 'beekeeping@misoftwaresolutions.com'
FTP_PASS = 'Beekeeping@2026'
try:
    ftp = ftplib.FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    files = ftp.nlst()
    print("beekeeping.sql exists:", 'beekeeping.sql' in files)
    print("import_db.php exists:", 'import_db.php' in files)
    ftp.quit()
except Exception as e:
    print(e)
