#!/usr/bin/env python3
"""Deploy BAUTERM to Hostpoint via FTP"""
import ftplib
import os
import sys

FTP_HOST = 'sl1761.web.hostpoint.ch'
FTP_USER = 'bauterm@bauterm.ch'
FTP_PASS = 'Novitr@vnik1'
LOCAL_DIR = os.path.join(os.path.dirname(__file__), 'docs')
REMOTE_DIR = '/'  # Will try to find the right dir

def upload_dir(ftp, local_path, remote_path):
    """Recursively upload a directory"""
    for item in sorted(os.listdir(local_path)):
        local_item = os.path.join(local_path, item)
        remote_item = remote_path + '/' + item

        if item.startswith('.') and item not in ['.htaccess']:
            continue
        if item == 'designs':
            continue  # Skip designs folder

        if os.path.isdir(local_item):
            # Create remote directory if needed
            try:
                ftp.mkd(remote_item)
                print(f'  [DIR]  {remote_item}')
            except ftplib.error_perm:
                pass  # Directory exists
            upload_dir(ftp, local_item, remote_item)
        else:
            with open(local_item, 'rb') as f:
                ftp.storbinary(f'STOR {remote_item}', f)
                size = os.path.getsize(local_item)
                print(f'  [FILE] {remote_item} ({size:,} bytes)')

def main():
    print(f'Connecting to {FTP_HOST}...')
    ftp = ftplib.FTP()
    ftp.connect(FTP_HOST, 21, timeout=30)
    ftp.login(FTP_USER, FTP_PASS)
    ftp.encoding = 'utf-8'
    print(f'Connected. Server: {ftp.getwelcome()}')

    # List root to find web directory
    print('Root directory contents:')
    items = ftp.nlst('/')
    for item in items:
        print(f'  {item}')

    # Try common web root paths
    web_root = '/'
    for candidate in ['/web', '/www', '/htdocs', '/public_html', '/']:
        try:
            ftp.cwd(candidate)
            web_root = candidate
            print(f'Using web root: {web_root}')
            break
        except:
            continue

    print(f'\nUploading files from {LOCAL_DIR} to {web_root}...')
    upload_dir(ftp, LOCAL_DIR, web_root)

    print('\nDone! Files uploaded successfully.')
    ftp.quit()

if __name__ == '__main__':
    main()
