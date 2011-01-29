{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="pageTitle"}Universitas Mobile Internet{/block}

{block name='description'}Universitas is launching a university-wide mobile initiative to aggregate and deliver useful, usable, mobile-appropriate content to the Universitas community, locally and worldwide.{/block}

{block name="header"}
  	<div id="utility_nav">
    	<a href="http://www.universitas.edu" target="_blank">Universitas.edu</a>
        &nbsp;|&nbsp;
        <a href="http://news.universitas.edu/contact/#dComm" target="_blank">Contact</a>
        &nbsp;|&nbsp;
        Share &nbsp; <a href="http://facebook.com/universitas" title="Facebook" target="_blank"><img src="/modules/info/images/facebook.png" alt="facebook"></a>  
		&nbsp;
		<a href="http://twitter.com/universitas" title="Twitter" target="_blank"><img src="/modules/info/images/twitter.png" alt="twitter"></a>
    </div><!--/utility_nav-->
    
    <div id="logo">
    	<img src="/modules/info/images/site_logo.png" alt="Universitas Mobile Internet" border="0" />
    </div>
    
    <h1>m.universitas.edu</h1>
    <p>
        is a university-wide mobile initiative to aggregate and deliver useful,
		usable, mobile-appropriate content to university communities, locally and
		worldwide.
    </p>
{/block}
    
{block name="content"}
  	<div class="leftcol">
    	<h2>Mobile Web Application</h2>
        <p>
            You can reach the mobile web application by going to <a href="/">m.universitas.edu</a> on your
            web browser on any internet-enabled mobile device. The mobile web
            application includes all the features listed at right.
        </p>
        <p>
          <a id="preview" href="/home/" target="UniversitasMobile">Click here to preview the site on your desktop or laptop.</a>
        </p>
    	<h2>Native iPhone Application</h2>
        <p>
        	The Universitas iPhone app can be used with the iPhone 4, 3GS, and 3G hardware, 
            but users must download the free iOS 4 software update. 
        </p>
        <table cellpadding="0" cellspacing="0" id="download" align="right">
          <tr>
            <td>
        	Download iPhone app 
            </td>
            <td>
            <a href="http://itunes.apple.com/us/app/universitas-mobile/id389199460" target="_blank"><img src="/modules/info/images/AppStoreBadge.png" alt="Universitas Mobile iPhone App Download" width="90" height="31" /></a>
            </td>
          </tr>
        </table>
        
        <div class="clr"></div>
        
    	<h2>What's next?</h2>
        <p>
            We recognize that this is just the starting point for providing a better
            Universitas mobile experience for students, faculty, staff, alumni, and
            visitors. We will continue to roll out new features in the future.
        </p>
        
        <p>
          <a id="feedback" href="mailto:dev@modolabs.edu">
            <strong>Feedback</strong>
            <br />
            Find a bug? Want to recommend a feature? Your ideas and usage will 
            help inform future development. Please send your feedback to 
            <span class="address">dev@modolabs.edu</span>.
          </a>
        </p>
        
    </div><!--/leftcol-->
    
    <div class="rightcol">
    	<h2>Features</h2>
        
    	<table cellpadding="0" cellspacing="0" id="features">
          <tr>
            <td>
              <img src="/modules/info/images/icons/people.png" alt="People Directory" />
            </td>
            <td>
            <h2>People Directory</h2>
            <p>
            Search by first and last name for phone numbers, email addresses, and office location for Universitas students, faculty and staff. Note that contact details vary and are informed by individual privacy settings.
            </p>
            </td>
          </tr>
          <tr>
            <td>
              <img src="/modules/info/images/icons/calendar.png" alt="Events" />
            </td>
            <td>
            <h2>Events</h2>
            <p>
            Find out what&#8217;s going on today at Universitas or coming up soon. Events from the Universitas calendar are available by category with the date and time, and location. Where available, you can click on the event’s location to see it on the map.
            </p>
            </td>
          </tr>

          <tr>
            <td>
              <img src="/modules/info/images/icons/news.png" alt="News" />
            </td>
            <td>
            <h2>News</h2>
            <p>
            Get the latest news from the Universitas newspaper which features latest news about the Universitas community, arts and culture, and science and research. You can share articles using email, Facebook, or Twitter. 
            </p>
            </td>
          </tr>

          <tr>
            <td>
              <img src="/modules/info/images/icons/search.png" alt="Search" />
            </td>
            <td>
            <h2>Search</h2>
            <p>
            Search offers a quick and powerful way to search across content from most or all of the features above. Search results will be presented grouped by type, and recent search queries will be saved and auto-suggested for even faster and easier access.
            </p>
            </td>
          </tr>
          
        </table>
    </div><!--/rightcol-->

	<div class="clr"></div>
{/block}
    
{block name="footer"}
  <span class="copyright">&copy; 2010 <a href="http://www.universitas.edu" target="_blank">Modo Labs, Inc.</a></span>
{/block}

{block name="footerJavascript"}
  {literal}
    var _sf_async_config={uid:2327,domain:"m.universitas.edu"};
    (function(){
      function loadChartbeat() {
        window._sf_endpt=(new Date()).getTime();
        var e = document.createElement('script');
        e.setAttribute('language', 'javascript');
        e.setAttribute('type', 'text/javascript');
        e.setAttribute('src',
           (("https:" == document.location.protocol) ? "https://s3.amazonaws.com/" : "http://") +
           "static.chartbeat.com/js/chartbeat.js");
        document.body.appendChild(e);
      }
      var oldonload = window.onload;
      window.onload = (typeof window.onload != 'function') ?
         loadChartbeat : function() { oldonload(); loadChartbeat(); };
    })();
    
      // Google master profile start
      var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
      document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' 				type='text/javascript'%3E%3C/script%3E"));
    
      try {
      var pageTracker = _gat._getTracker("UA-2923555-18");
      pageTracker._trackPageview();
      } catch(err) {}
      // Google master profile end
  {/literal}
{/block}
