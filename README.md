## WebMail Lite 7.8 - Personal version with several bugfixes and updates.

## Changelog:

TYPE | ELEMENT | DETAILS
:---: | :---: | --- |
Added | Language | Full Spanish translation.
Added | Mobile devices | Better mobile compatibility.
Added | Login Screen | Captcha implemented by default.
Added | Login Screen | Disabled 'Remember me' checkbox.
Added | Login Screen | Disabled 'Language selection' selectbox.
Added | Login Screen | Better spacing between icons and textboxs.
Added | Rich Text Editor | Bottom divisor line in the format toolbar.
Bugfix 1 | Login Screen | Password icon size focused fixed.
Bugfix 2 | Rich Text Editor | Textarea section now has a full width.
Bugfix 3 | Rich Text Editor | Removed extra breakline below signature.
Bugfix 4 | Rich Text Editor | Prevents the vertical scrollbar to be missplaced.
Bugfix 5 | Plugin Captcha | Plugin updated for the latest version of Webmail Lite 7.
Bugfix 6 | Compose Window | Prevents the attachment list to overlapping the text editor.

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

## If you modified some front-end files and can't see any changes

Delete every folder and file that you see in **data/cache** and reload the page.
