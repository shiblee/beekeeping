import ftplib
import os

FTP_HOST = '184.168.114.12'
FTP_USER = 'beekeeping@misoftwaresolutions.com'
FTP_PASS = 'Beekeeping@2026'

def upload_dir(ftp, local_dir, remote_dir):
    try:
        ftp.mkd(remote_dir)
    except Exception as e:
        pass # Directory might already exist
    
    ftp.cwd(remote_dir)
    for item in os.listdir(local_dir):
        if item in ['.git', '.DS_Store', 'test_ftp.py', 'deploy_ftp.py', 'beekeeping.sql']:
            continue
            
        local_path = os.path.join(local_dir, item)
        if os.path.isfile(local_path):
            print(f"Uploading {local_path} to {remote_dir}/{item}")
            with open(local_path, 'rb') as f:
                ftp.storbinary(f'STOR {item}', f)
        elif os.path.isdir(local_path):
            upload_dir(ftp, local_path, f"{remote_dir}/{item}")
            ftp.cwd(remote_dir) # Go back to current directory

try:
    ftp = ftplib.FTP(FTP_HOST)
    ftp.login(FTP_USER, FTP_PASS)
    print("Logged in. Starting upload...")
    
    upload_dir(ftp, '.', '/')
    
    ftp.quit()
    print("Done!")
except Exception as e:
    print("Error:", e)
