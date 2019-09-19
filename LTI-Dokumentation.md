Nutzer für LTI in der Opencast Admin UI Anlegen
-----------------------------------------------
- lti-user 
- ROllEN: ```ROLE_ADMIN```

LTI in Opencast Konfigurieren:
------------------------------

- edit: ```/opencastfolder/etc/security/mh_default.xml```
  enable:
  ```<ref bean="oauthProtectedResourceFilter" />```

- edit: ```/opencastfolder/etc/org.opencastproject.kernel.security.OAuthConsumerDetailsService.cfg```
  set :
  ```
  oauth.consumer.name.1=CONSUMERNAME
  oauth.consumer.key.1=YOURKEY
  oauth.consumer.secret.1=YOURSECRET
  ```

- edit ```/opencastfolder/etc/custom.properties```
  enable:
  ```org.opencastproject.security.custom.roles.pattern=^[0-9a-f-]+_(Learner|Instructor)$```


Dokumentation at opencast.org
[1]https://docs.opencast.org/develop/admin/modules/ltimodule/#configure-lti
