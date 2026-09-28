# GIT-NAS Documentation
 
This guide covers connecting to a NAS-hosted Git repository, pushing/pulling changes, and configuring it for safe use as a live web directory.

## Installing Git

If it is your first time seeing and hearing the word Git, it is a version control tool used regularly in most tech jobs to keep track of changes in code and production.

To install git you first must navigate to the website
```bash
https://git-scm.com/install/windows
```

Once there, download git for your machine, once it is installed you will want to check its version
```bash
git --version
```
**Expected output:**
```bash
git version x.xx.x
```
**If you would like to learn a lot more about Git, this video does a good job at teaching the basics**
```bash
https://www.youtube.com/watch?v=zTjRZNkhiEU&t=7251s
```


## SSH Access to NAS

Accessing the NAS via SSH here is not necessary. It is useful to make sure that you are pushing to the correct branch within the NAS. To do so, open a terminal on your local machine.

**Connect to the NAS:**
```bash
ssh [username]@10.9.5.21
```

**Navigate to the repository folder:**
```bash
cd /var/services/web/sts
```

**Check the current branch**
```bash
git branch
```

**Expected output:**
```
    Branch1 
    * main <- indicated that this is the current branch, always use main in our case
    Branch3
```
We are using a mono-branch repository where we just overwrite all of the changes via one branch.

**Change current branches**
```bash
git checkout [new_branch]
```

## Prepare NAS Repository

You should not have to do this step, once again, it has already been done.

Our current NAS repo is non-bare (used by the web server), you need to allow Git to update the checked-out branch safely:

```bash
git config receive.denyCurrentBranch updateInstead
```

This ensures pushes from your local system update both the working directory and the Git history.

**Do this once on the NAS repo.**

## Configure Your Local Repository

This is needed if you want to be able to store your data in a repository on your personal machine. You will be pushing and pulling code via this repository.

**Navigate to your local project folder:**
```bash
cd /path/to/local/project
```

**Initialize the current repository you just made**
```bash
git init
```

**Add the NAS repository as a remote:**
```bash
git remote add nas ssh://[username]@10.9.5.21/var/services/web/sts
```

**Verify the remote:**
```bash
git remote -v
```

**Expected output:**
```bash
nas    ssh://[username]@10.9.5.21/var/services/web/sts (fetch)
nas    ssh://[username]@10.9.5.21/var/services/web/sts (push)
```


**Check which branch you are using**

As said earlier, we are using a monobranch system, therefore you will need a main branch on your local repository. First check what your branch is.

```bash
git branch
```

**Expected output:**
```bash
* main
```

Please note that if your branch is master or something else, you must create a new branch named main.

**Adding a branch**
```bash
git branch main
```

**Check and make sure the branch is there**
```bash
git branch
```
**Expected output:**
```bash
main
* master
```

From here, please move to the main branch so that branches are matching from the local repo to the NAS repo.

**Checkout to the correct branch**
```bash
git checkout main
```
**Confirm you moved branches**
```bash
git branch
```
**Expected output:**
```bash
* main
master
```


## Pushing Changes to NAS

**First when you have made changes, it is common to check the status of the file:**
```bash
git status
```

**Expected output:**
```
modified:   folder/file
added:  folder/file
```

**After that you must add the code to the repository. There are two ways to do this:**

Add individual files
```bash
git add folder/file1 folder/file2
```

And add all files
```bash
git add .
```

**After adding it is common to check the status again. After that, you will commit the change and add a meaningful comment to the code. This comment will explain what was changed and such:**
```bash
git commit -m "Add an example of way to comment your code additions"
```

**Before pushing, always pull first to avoid overwriting any changes:**
```bash
git pull nas main
```
*Replace `main` with your branch name if different.*
This ensures you have the latest changes before making new commits and that your local repo is synced with the NAS.


**Push your local changes:**
```bash
git push nas main
```

**For first-time pushes, set the upstream branch:**
```bash
git push -u nas main
```

## Error Handling

Git is never going to be 100% correct and you will almost always run into some sort of error or issue. This is to showcase what common errors may be and how to fix them so that you can get back to coding.


### The NAS has unstaged changes to the code

This code below is showing what will be presented to you after you try to pull and push.

**ERROR**
```bash
 ! [remote rejected] main -> main (Working directory has unstaged changes)
 error: failed to push some refs to 'ssh://10.9.5.21/var/services/web/sts'
```

**FIX**

To fix the error you must log into the NAS and commit the current change that is there

```bash
ssh [username]@10.9.5.21

cd /var/services/web/sts

git status
```

**Expected output:**
```
modified:   folder/file
added:  folder/file
```

From here you must add and commit the changes that were made to the NAS directly

```bash
git add folder/file1 folder/file2

git commit -m "Add an example of way to comment your code additions"
```

This is all you need to do! After you do this you should be able to go back to your regular terminal and pull and push your changed code!

### Your code on your local repo is different then the code that is in the NAS
This is a normal error you will see when you get back from a few days of missing work or when a lot of changes have been made since you last made changes.

**ERROR**
```bash
! [rejected]        main -> main (fetch first)
error: failed to push some refs to 'ssh://10.9.5.21/var/services/web/sts'
```

**FIX**

For the simple fix of this, all you need to do is pull the code from the website.

```bash
git pull nas main
```

This will give you a merge text that you will just close as it is telling you what changed. After this, you should be clear to push your code to the repository!

### Got rid of something that you did not mean to?

This can occasionally happen when merging and something is deleted and you need to go back to fix it. For example: Deleting all the files on the server... cough cough Vincent...

**FIX**

There are two ways to do a revert, you can either revert to the last commit by using 

```bash
git revert HEAD
```

Or you can revert to a specific commit if you know its hash

```bash
git log --oneline
```

This will allow you to see the hashes of the commits and comments of what was changed. To exit, press q

```bash
git revert [commit-hash]
```

Make sure after your revert you commit and push the changes

```bash
git commit -m "Message of what you were reverting"

git push -u nas main
```