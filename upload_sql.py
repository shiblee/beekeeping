import ftplib
FTP_HOST = '184.168.114.12'
FTP_USER = 'beekeeping@misoftwaresolutions.com'
FTP_PASS = 'Beekeeping@2026'

try:
    ftp = ftplib.FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    print("Logged in. Uploading files...")
    
    with open('import_db.php', 'rb') as f:
        ftp.storbinary('STOR import_db.php', f)
        
    with open('beekeeping.sql', 'rb') as f:
        ftp.storbinary('STOR beekeeping.sql', f)
    
    ftp.quit()
    print("Done!")
except Exception as e:
    print("Error:", e)
