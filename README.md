# Landwirt Subscription Handling Plugin

## SETUP

1. Installation of Mamp
2. start the server and install wordpress via wp-cli commands
3. curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
4. php wp-cli.phar core download
5. In PHPMyadmin: create a new database and then install WordPress.
6. Create a new directory "wp-stars" in the root of your wordpress folder
7. do a git clone 'https://github.com/wp-stars/lawi-subscription-handling.git'
8. cd to your wp-content/plugins folder
9. create a symlink: ln -s path/to/wp/wp-stars/lawi-subscription-handling/lawi-subscription-hanlding
10. Install WooCommerce and activate the Plugin

## Workflow

1. checkout the masterbranch -> git checkout master
2. pull the latest changes -> git pull origin master
3. create a new branch -> git checkout -b 'mrx/registration-form' (your suffix/feature you're working on)
4. commit your changes into the branch -> git commit -m 'feat: update the name input field'
5. When you finished your feature/fix then do do one git pull origin master to get the newsest changes
6. merge your merge conflicts
7. push your branch to github
8. create a pull request in github
9. merge your branch into the development branch
10. if development branch works fine, merg the development branch into the master branch

## Rule number 1 Never push into the master!
# Date
- Date format YYYY-mm-dd
- Use DateTime()
- Standard DateTime Zone = Vienna


## possible commit styles:

'fix: i fixed some bug'
'feat: i added a news function'

## Stripe CLI for Testing

1. Install Woocommerce Stripe Addon
2. Install Stripe CLI on your local Machine (easy with homebrew) https://stripe.com/docs/stripe-cli
3. Listen for Webhook events: https://dashboard.stripe.com/test/webhooks/create?endpoint_location=local
4. Test your Payments with testcard: 4242424242424242


```console
stripe listen --forward-to localhost:4242/webhook
```

# Automatic Deployment

> Dieses Plugin zeigt wie lokals Arbeiten bzw. ein Github Workflow für Plugins aussehen kann.


# Table of contents

- [General Information](#user-content-general-information)     
- [Dev Process](#user-content-dev-process)    
    - [Installation Dev](#user-content-installation-dev)    
    - [Git Handling](#user-content-git-handling)    
    - [Deploy Process](#user-content-deploy-process)    

----------

# General Information  
***URL to ZIP Files***: [http://documentation.wp-stars.com/wps-plugin/wps-plugin.zip](http://documentation.wp-stars.com/wps-plugin/wps-plugin.zip)     

----------

# Dev Process
As we have in this project some "rules" that all works as aspected here are our "guides" for a smooth workflow.

## Installation Dev
Diese Vorgehensweise ist nötig, dass du in deinem WP System arbeiten kannst, der automatische Prozess zum erstellen des Zip Files aber nicht gestört wird.

1. Einen Ordner 'wps-development' innerhalb vom WP Root anlegen
2. In den Ordner wechseln
3. Projekt auschecken
4. Anschließend einen SymLink anlegen: `$ ln -s /PFAD_ZU_WP/wps-development/wps-plugin /PFAD_ZUM_WP/plugins/`

## Git Handling
- **DON'T** work in master
    - for each feature use a single branch `prefix/feat/feature-name-here`
    - after finishing the new feature make a pull request to merge the new feature into the master
- Commit Messages
    - use fix for commits: `git commit -m 'fix: what i've done'`
    - use feat for finished features: `git commit -m 'feat: feature X is finished'`
    - use doc for readme adaptions: `git commit -m 'doc: readme adaptions'`
- Release MAIN Version:
    - `git commit`
    - if vi(m) press `i` (for insert mode)
    - Type `perf: commit message` and another line with `BREAKING CHANGE: ....`
    - if vi(m) press `ESC`
    - if vi(m) press `:wq` (for write and quit)
```
    perf: remove graphiteWidth option

    BREAKING CHANGE: The graphiteWidth option has been removed.
    The default graphite width of 10mm is always used for performance reasons.
```
   
## Deploy Process
We've "developed" a workflow you can find in `.github/workflows/main.yml`.    
*Short Description of Workflow:*    
- will be executed on push in master branch
- create tags if you use right commit messages
- replace `%%version%%` placeholder in "Readme.md", "info.json" and "wp-plugin.php" with new released version
- create zip file of relevant data: all in `wp-plugin` folder
- upload new zip to [http://documentation.wp-stars.com/wps-plugin/wps-plugin.zip](http://documentation.wp-stars.com/wps-plugin/wps-plugin.zip)