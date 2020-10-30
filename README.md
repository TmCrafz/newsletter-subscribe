# newsletter-subscribe
A simple script for subscribing for email newsletter  

##  json mode and redirect mode

You can choose between json output (default, useful for async use with ajax/javascript) as result or redirecting to configurable urls (302). The redirection urls are configure in config/config.ini file and can kept empty when not used. To set redirect_mode you have to add ```redirect=true``` as get argument to the url. For example ```https://test.de?redirect=true```. Some functions like subscribing and confirmaning can lead into sending an email by itself with an url. If the urls response should get handled in redirect mode you have to add ```redirect_resulting_email=true```as get parameter to the url. 
Example:
Subscribe to email in redirect mode and the leading confirmation url should also be in redirect mode the url would be:
```
https://test.de?subscribe&redirect=true&redirect_resulting_email=true
```
Subscribe to email in default Json mode and the resulting confirmation should be in redirect mode:
```
https://test.de?subscribe&redirect_resulting_email=true
```
Both in Json Mode:
```
https://test.de?subscribe
```  

### Json Output
Success:  
```
{"success":true,"message":""}
```
Fail/Errors (message is different depending on error):
```
{"success":false,"message":"error_invalid_email"}
```
  
  
## Error output
The flag ```print_errors``` in ```config.ini``` should be set to ```"false"``` in productive use.  