ApiToPostman


18th May 2018

I use POSTMAN for testing web serrvices I'm building. I create client requests in POSTMAN and then can use
these over and over again in testing. Once completed a full API test suit can be produced.

Now sometimes the client requests already exist, from some client software. If you need the request to be
in POSTMAN you can manually create the request, OR you can use my class to build the POSTMAN request for you.

All you need do is add a few lines in your PHP entry point, to include the class. From then on, all incoming requests
will be saved in a POSTMAN collection that can be imported.

An example where this has come in use is when I tookk over the support of an exsiting web service. All I
had to do was add a few lines of code to the entry point, then run the client sofwatre through all its
options. At the end a I have a POSMAN collection with all the calls. This helps me understand how things work,
and a means to re-run any requests. I can also set up a full suite of tests, so that I can be sure I haven't 
broken anything.

I hope you find it useful. 

Greg Ambrose





