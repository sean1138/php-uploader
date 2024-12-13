# php-uploader
mainly to upload images

will upload files to a /uploads/ subdirectory so create one.

## issues:
1. paste from clipboard is fail, 1st attempts to get bot to fix it was all kinds of fail
2. the bot didn't get all the features i asked for, not surprising
3. "copy link" link doesn't have the correct URL
4. file expiration not functional AFAIKRN but an option i'd like to have
5. security: you will probably want to lock this down via .htaccess

## chatjippity-4o starting prompt:
> let's make a file uploader!
>
> file uploader features:
> 1, support drag and drop file, paste data from clipboard, and to browse local disks for file to upload.
> 2, support "upload from URL" when user pastes URL to a file
> 3, show progress bar while uploading
> 4a, after upload, display filename, file size, file dimensions, and link to file that can be copied by clicking it
> 4b, if user pasted clipboard then saved/uploaded filename shall star with current dat and time in yyy.mm.dd_hhmmss_ time/date format + an 8 digit random alphanumeric string after the last underscore, 24 hr format from user's local time.
> 4c, can we save the filename, file size, file dimensions, file upload date, file uploader ip address to another html or php file for the admin to view later?
> 5, should still be able to upload more files after uploading the first, keep info from previous upload(s) shown on the page, add subsequent uploaded files' info to top of the list, no page reloading if possible.
> 6, is there a way we can check to see if the same file has been uploaded already? some sort of hash check of the content in addition to filename collision check?
> 7, have a dropdown menu above the file drop/past/browse zone/element with options to set file expiration/deletion after a period of time.  default option = never, 1 hour, 12 hours, 1 day, 3 days, 7 days
> 8, backend code needs to be able to set a file size limit
> 9, backend code needs to have allow list for file types
> 10, let me know of any potential features or issues you think i missed?

