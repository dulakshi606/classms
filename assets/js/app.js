document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-confirm]");
    if(btn){
      const msg = btn.getAttribute("data-confirm") || "Are you sure?";
      if(!confirm(msg)) e.preventDefault();
    }
  });
  