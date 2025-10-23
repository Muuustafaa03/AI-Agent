<?php
// Shared dark-mode styles + toggle.
?>
<style>
  :root {
    --bg:#f3f4f6; --text:#0f172a; --muted:#475569;
    --card:#ffffff; --card-muted:#f8fafc; --border:#e5e7eb;
    --input-bg:#ffffff; --input-text:#0f172a; --placeholder:#9ca3af;
  }
  html.dark {
    --bg:#0b1220; --text:#e5e7eb; --muted:#cbd5e1;
    --card:#0f172a; --card-muted:#111827; --border:#374151;
    --input-bg:#111827; --input-text:#e5e7eb; --placeholder:#94a3b8;
  }

  body{background:var(--bg);color:var(--text);}
  .bg-white{background-color:var(--card)!important;}
  .text-gray-600,.text-gray-500{color:var(--muted)!important;}
  .border{border-color:var(--border)!important;}
  .shadow{box-shadow:0 1px 2px rgba(0,0,0,.10),0 6px 20px rgba(0,0,0,.12)!important;}

  /* Inputs / textarea / pre */
  textarea,input[type="text"],select,pre{
    background:var(--input-bg)!important;
    color:var(--input-text)!important;
    border-color:var(--border)!important;
  }
  textarea::placeholder,input::placeholder{color:var(--placeholder)!important;}
  pre.whitespace-pre-wrap{white-space:pre-wrap;}

  /* Buttons that used .bg-gray-100 were low-contrast in dark mode */
  .bg-gray-100{
    background-color:var(--card-muted)!important;
    color:var(--text)!important;
    border-color:var(--border)!important;
  }
</style>
<script>
  (function(){
    const saved = localStorage.getItem("theme");
    if (saved === "dark") document.documentElement.classList.add("dark");
  })();
  function toggleTheme(){
    document.documentElement.classList.toggle("dark");
    const isDark = document.documentElement.classList.contains("dark");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    document.querySelectorAll("[data-theme-toggle]").forEach(b=>{
      b.textContent = isDark ? "Light Mode" : "Dark Mode";
    });
  }
</script>
