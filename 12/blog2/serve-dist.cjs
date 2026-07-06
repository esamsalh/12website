const http = require("http");
const fs = require("fs");
const path = require("path");
const dir = "C:/xampp/htdocs/12/blog2/dist";
const mime = { ".html":"text/html;charset=utf-8",".css":"text/css",".js":"application/javascript",".png":"image/png",".jpg":"image/jpeg",".svg":"image/svg+xml",".xml":"application/xml",".json":"application/json",".ico":"image/x-icon" };
http.createServer((req,res)=>{
  let url = req.url.split("?")[0];
  if (url.endsWith("/")) url += "index.html";
  let fp = path.join(dir, url.replace(/\//g, "\\"));
  try {
    let stat = fs.statSync(fp);
    if (stat.isDirectory()) {
      fp = path.join(fp, "index.html");
      if (!fs.existsSync(fp)) { res.writeHead(404); res.end("404"); return; }
    }
  } catch { res.writeHead(404); res.end("404"); return; }
  let ext = path.extname(fp);
  res.writeHead(200,{"Content-Type":mime[ext]||"application/octet-stream"});
  fs.createReadStream(fp).pipe(res);
}).listen(3000, ()=>console.log("Server running at http://localhost:3000"));
