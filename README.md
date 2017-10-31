# Members: Twitter Login

> Logs in users using Twitter oAuth

### SPECS ###

Automatically creates account and logs in the user.

### REQUIREMENTS ###

- Symphony CMS version 2.7.x and up (as of the day of the last release of this extension)
- Members extension version 1.9.0

### INSTALLATION ###

- `git clone` / download and unpack the tarball file
- Put into the extension directory
- Enable/install just like any other extension

You can also install it using the [extension downloader](http://symphonyextensions.com/extensions/extension_downloader/).

For more information, see <http://getsymphony.com/learn/tasks/view/install-an-extension/>

### HOW TO USE ###

- Enable the extension
- Create a new Member section with only a email field (no password)
	- Optionally, create a input/textarea/textbox field for the Twitter handle
- Set the required configuration values:

```php
###### MEMBERS_TWITTER_LOGIN ######
'members_twitter_login' => array(
    'key' => 'REPLACE ME',
    'secret' => 'REPLACE ME',
    'twitter-handle-field' => 'REPLACE ME with a field id if you want to save the twitter handle',
),
########
```

- Create a page and attach the "Members: Twitter login" event on it
- Create the login form:

```html
<form action="/twitter/" method="POST">
	<input type="hidden" name="redirect" value="/twitter/" />
	<input type="hidden" name="members-section-id" value="<Your section id>" />
	<input type="hidden" name="member-twitter-action[login]" value="Login" />
	<button>Log in with Twitter</button>
</form>
```

This form will redirect the user to twitter and then twitter will redirect the user your redirect url set in your twitter app setting.

- Add another form to handle the actual log in process when the user comes back from twitter. This form can be auto-submitted

```xslt
<xsl:if test="string-length(/data/params/url-oauth-token) != 0">
    <form id="twitterform" method="POST" action="{$current-url}/">
        <input type="hidden" name="oauth_token" value="{/data/params/url-oauth-token}" />
        <input type="hidden" name="oauth_verifier" value="{/data/params/url-oauth-verifier}" />
        <button>Validate</button>
    </form>
    <script>if (window.twitterform) twitterform.submit();</script>
</xsl:if>
```

- If everything works, the user will be redirected to the 'redirect' value, just like the standard Members login.

### LICENSE ###

MIT <http://deuxhuithuit.mit-license.org>

*Voila !*

Come say hi! -> <https://deuxhuithuit.com/>
