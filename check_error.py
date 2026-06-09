import ftplib
FTP_HOST = '184.168.114.12'
FTP_USER = 'beekeeping@misoftwaresolutions.com'
FTP_PASS = 'Beekeeping@2026'
try:
    ftp = ftplib.FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    with open('error_log', 'wb') as f:
        ftp.retrbinary('RETR error_log', f.write)
    ftp.quit()
except Exception as e:
    print(e)
