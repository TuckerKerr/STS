# GPG Commit Signing
 
## 1. Setting Up Signed Commits with GPG

### 1.1 Generate a GPG Key

**Check for existing GPG keys:**
```bash
gpg --list-secret-keys --keyid-format=long
```

**Generate a new GPG key (if needed):**
```bash
gpg --full-generate-key
```
- Select `RSA and RSA` (default)
- Key size: `4096` bits
- Expiration: Choose based on your preference (0 = no expiration)
- Enter your name and email (use the same email as your Git config)

### 1.2 Configure Git to Use GPG

**List your GPG keys and copy the key ID:**
```bash
gpg --list-secret-keys --keyid-format=long
```

Example output:
```
sec   rsa4096/ABC123DEF456 2024-01-01 [SC]
```
The key ID is `ABC123DEF456`

**Tell Git to use your GPG key:**
```bash
git config --global user.signingkey ABC123DEF456
```

**Enable commit signing by default:**
```bash
git config --global commit.gpgsign true
```

**Set GPG program (if needed):**
```bash
git config --global gpg.program gpg
```

### 1.3 Export Your Public Key

**Export your GPG public key:**
```bash
gpg --armor --export ABC123DEF456
```

Copy the output (including `-----BEGIN PGP PUBLIC KEY BLOCK-----` and `-----END PGP PUBLIC KEY BLOCK-----`).

**Add this public key to:**
- GitHub: Settings → SSH and GPG keys → New GPG key
- GitLab: Preferences → GPG Keys
- Other Git platforms as needed

### 1.4 Sign Commits

**Commits will now be signed automatically.** To manually sign a commit:
```bash
git commit -S -m "Your commit message"
```

**Verify signed commits:**
```bash
git log --show-signature
```

### 1.5 Troubleshooting GPG

**If you get "gpg: signing failed: Inappropriate ioctl for device":**
```bash
export GPG_TTY=$(tty)
```

Add to your `~/.bashrc` or `~/.zshrc`:
```bash
echo 'export GPG_TTY=$(tty)' >> ~/.bashrc
```

**Test GPG signing:**
```bash
echo "test" | gpg --clearsign
```