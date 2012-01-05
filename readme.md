phpCSS - Open source PHP5 library for CSS
====================

**phpCSS** is a PHP5 library that converts CSS (Cascading Styling Sheets) into an semantic (array) format. This can be used to select certain elements, and also to compress CSS with your full control. phpCSS is available for free under the MIT license.

Author
---------------------
The library was created by Bilawal Hameed, a 18 year old entrepreneur and programmer from Manchester. I decided to release this library for free because it would foster a whole new community. I've used plenty of libraries in the past that are open source, and you could say this is the first step for me giving back to the community.

It is currently under __active development__ by myself. I hope other developers can contribute and help me release even better things in the future. You can see my thoughts on phpCSS on a regular basis over at [my website](http://www.bilawal.co.uk/).

Purpose
---------------------
The purpose of this library is to simple: to break down CSS files into arrays, and sort them semantically.

- In less technical terms, that means, we can see CSS files and successfully extract information from them and convert them in different options. One of them will be that it can detect the RGB (Red Green Blue) values from a Hexadecimal (#FF3300) so you know how much red, green and blue is used in a certain element.

- It can foster great usages, and I will discuss a couple I had when developing phpCSS. Firstly, with the information all fully formatted and available in certain other formats, it allows you to then make a converter to put it back into CSS but this time it won't have any clutter. Another example may be that you may have a tool that adds a certain class to a CSS file, and with this, it allows you to do it a semantic and orderly fashion.

- By being able to analyse them, developers can code up great programs that may make our future a lot better. I've just started that journey. phpCSS can be the start to digital syntax conversion into server-side programming languages.


Example usage
---------------------
While there are examples available in the `/examples` folder, I've displayed a couple below.

Once you have successfully installed phpCSS.php on a PHP5 server (LAMP recommended) you need to insert this code:

    include "./src/phpCSS.php";
    $phpcss = new phpCSS('http://www.domain.com/style.css');
    print_r($phpcss->decode());


The code above will decode a CSS file from **http://www.domain.com/style.css** - though you can insert CSS in the same field as demonstrated below:

    include "./src/phpCSS.php";
    $phpcss = new phpCSS(
    	'body {
    		border:			1px;
    		margin:			0px;
    		background:	4px;
    	}'
    );
    print_r($phpcss->decode());

For more advanced users, you can set custom rules, such as disabling advanced decoding (see inline documentation to find out more) or to disable output nested organising. So far, **disable_advanced_decoding** disables the advanced decoding. And **disable_organise** which disables the nested organising.

    include "./src/phpCSS.php";
    $phpcss = new phpCSS(
    	'body {
    		border:			1px;
    		margin:			0px;
    		background:	4px;
    	}'
    );
    $phpcss->set_rule('disable_advanced_decoding');
    
	print_r($phpcss->decode());