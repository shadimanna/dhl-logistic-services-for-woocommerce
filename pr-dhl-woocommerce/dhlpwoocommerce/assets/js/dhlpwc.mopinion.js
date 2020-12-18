(function () {
  var id = "ur6x9i87liw75cfrzfdshuinnx3fy6ngw4k";
  var js = document.createElement("script");
  js.setAttribute("type", "text/javascript");
  js.setAttribute("src", "//deploy.mopinion.com/js/pastease.js");
  js.async = true;
  document.getElementsByTagName("head")[0].appendChild(js);
  var t = setInterval(function () {
    try {
      new Pastease.load(id);
      clearInterval(t)
    } catch (e) {
    }
  }, 50)
})();
