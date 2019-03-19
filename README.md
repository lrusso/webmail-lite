## WebMail Lite 7.8 - Personal version with several bugfixes and updates

## Changelog

TYPE | ELEMENT | DETAILS
:---: | :---: | --- |
Fixed | Login Screen | Password icon size focused fixed.
Fixed | Rich Text Editor | Textarea section now has a full width.
Fixed | Rich Text Editor | Removed extra breakline below signature.
Fixed | Rich Text Editor | Prevents the vertical scrollbar to be missplaced.
Fixed | Plugin Captcha | Plugin updated for the latest version of Webmail Lite 7.
Fixed | Compose Window | Prevents the attachment list to overlapping the text editor.
Fixed | Installer | Fixed logo image reference.
Updated | Language | Full Spanish translation.
Updated | Login Screen | Captcha implemented by default.
Updated | Login Screen | Disabled 'Remember me' checkbox.
Updated | Login Screen | Disabled 'Language selection' selectbox.
Updated | Login Screen | Better spacing between icons and textboxs.
Updated | Mobile devices | Viewport modified for better compatibility.
Updated | Rich Text Editor | Bottom divisor line in the format toolbar.

## How to install

1) Decompress the zip file in your server.
2) Browse to /install
3) Follow the instructions.
4) Delete the 'install' folder.
5) Browse to /adminapanel
6) Login with the username **mailadm** and your admin password to access the webmail system configuration.

**SUGGESTION:** Rename the 'adminpanel' folder to prevent people trying to try to find out your admin password.

## How to login with only the username

Modify or add the following lines to the file **data/settings/settings.xml**

```
<LoginFormType>Login</LoginFormType>
<LoginAtDomainValue>yourwebsite.com</LoginAtDomainValue>
```

## How to disable captcha

Delete the following lines from the file **data/settings/config.php**

```
'plugins.gregwar-captcha' => true,
'plugins.gregwar-captcha.options.limit-count' => 0,
```

## If you modified some files and can't see any changes

Delete everything in the folder **data/cache** and reload the page.
