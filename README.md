# newsletter-subscribe
A simple script for subscribing for email newsletter  
  
You can choose between json output as result or redirecting to configurable urls (302). The redirection urls are configure in config file and can kept empty when not used.  
Json output is of form:
Success  
```
{"success":true,"message":""}
```
Fail/Errors (message is different depending on error)
```
{"success":false,"message":"error_invalid_email"}
```
  
The flag ```print_errors``` in ```config.ini``` should be set to ```"false"``` in productive use.  