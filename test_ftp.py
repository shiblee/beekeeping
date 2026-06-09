import ftplib
import os

try:
    ftp = ftplib.FTP('184.168.114.12')
    ftp.login('beekeeping@misoftwaresolutions.com', 'Beekeeping@2026')
    print("Logged in!")
    print("Current dir:", ftp.pwd())
    ftp.retrlines('LIST')
    ftp.quit()
except Exception as e:
    print("Error:", e)
