Test module for Drupal 8 || 9.
This module adds form and HubSpot integration to Drupal.

REQUIREMENTS
------------

* Access to an SMTP server that will accept mail from you.
* The HubSpot php library be installed with composer.
  Refer to https://github.com/HubSpot/hubspot-php for details.

INSTALLATION INSTRUCTIONS
-------------------------

1.  Copy the files included in the tarball into a directory named "smtp" in
    your Drupal /modules/ or /modules/contrib/ directory.
2.  Enable the module:
    a.  Login as site administrator, visit the Extend page, and enable SMTP.
    b.  Run "drush pm-enable smtp" on the command line.
3.  Enable the Test task module on the Manage -> Extend page.
4.  Input API key on the Manage -> Configuration -> Web Servicies -> HubSot Settings Support page.
5.  Enjoy.


RELATED MODULES
---------------

You may find the following modules helpful:

 - mailsystem: controls which mail-related modules are used by other actions.
 - mimemail: Makes HTML email easier to send.
 - pet: Previewable Templating module
 - rules: can send emails when "events" happen, such as publishing a node.

NOTES
-----

You need to enter your API key for the module to work.
HubSpotHelper is the class responsible for communicating with the HubSpot server. 
It has a static method for checking the validation of the API key. It also allows 
you to create a contact or find an existing one via email, send a log that a 
message has been sent. The message is not sent from the HubSpot server, but 
from the Drupal site, since HubSpot does not provide such an API.

MailForm is the class that handles the submission of forms. Has a validation on 
email address, first and last name.
The submitForm () method shows the methods for using the HubSpotHelper.

***!!! The createContact () method can throw an exception !!!***
This is due to the closed email validation on the server, so don't forget to put 
this block in a try-catch (see the sample in the submitForm method).

MailHandler is a custom message sending service that requires hook_mail () to run. 
(See sample in .module file)

