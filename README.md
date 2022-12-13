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