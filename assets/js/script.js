(() => {
  "use strict";

  // ---------------------------------------------------------
  // Ano dinâmico no rodapé
  // ---------------------------------------------------------
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // ---------------------------------------------------------
  // Lazy load progressivo (native loading="lazy" + fade-in
  // assim que a imagem termina de carregar)
  // ---------------------------------------------------------
  const lazyImages = document.querySelectorAll('img[loading="lazy"]');

  const markLoaded = (img) => {
    img.classList.add("is-loaded");
  };

  lazyImages.forEach((img) => {
    if (img.complete && img.naturalWidth > 0) {
      markLoaded(img);
    } else {
      img.addEventListener("load", () => markLoaded(img), { once: true });
      img.addEventListener("error", () => markLoaded(img), { once: true });
    }
  });

  // Fallback com IntersectionObserver para navegadores sem
  // suporte a loading="lazy" nativo
  if (!("loading" in HTMLImageElement.prototype) && "IntersectionObserver" in window) {
    const ioImg = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const img = entry.target;
        if (img.dataset.src) img.src = img.dataset.src;
        observer.unobserve(img);
      });
    }, { rootMargin: "200px 0px" });

    lazyImages.forEach((img) => ioImg.observe(img));
  }

  // ---------------------------------------------------------
  // Menu mobile
  // ---------------------------------------------------------
  const navToggle = document.getElementById("navToggle");
  const mainNav = document.getElementById("mainNav");

  if (navToggle && mainNav) {
    const closeNav = () => {
      mainNav.classList.remove("is-open");
      navToggle.setAttribute("aria-expanded", "false");
      navToggle.setAttribute("aria-label", "Abrir menu");
    };
    const openNav = () => {
      mainNav.classList.add("is-open");
      navToggle.setAttribute("aria-expanded", "true");
      navToggle.setAttribute("aria-label", "Fechar menu");
    };

    navToggle.addEventListener("click", () => {
      const isOpen = mainNav.classList.contains("is-open");
      isOpen ? closeNav() : openNav();
    });

    mainNav.querySelectorAll(".nav__link, .nav__cta").forEach((link) => {
      link.addEventListener("click", closeNav);
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeNav();
    });
  }

  // ---------------------------------------------------------
  // Reveal-on-scroll para as seções institucionais
  // ---------------------------------------------------------
  const revealTargets = document.querySelectorAll(".reveal-io");

  if ("IntersectionObserver" in window && revealTargets.length) {
    const io = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.14, rootMargin: "0px 0px -60px 0px" }
    );

    revealTargets.forEach((el) => io.observe(el));
  } else {
    revealTargets.forEach((el) => el.classList.add("is-visible"));
  }

  // ---------------------------------------------------------
  // Header: leve reforço de contraste ao rolar
  // ---------------------------------------------------------
  const header = document.querySelector(".site-header");
  if (header) {
    const onScroll = () => {
      header.classList.toggle("is-scrolled", window.scrollY > 12);
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  // ---------------------------------------------------------
  // Campo de arquivo: mostra o nome selecionado
  // ---------------------------------------------------------
  const fileInput = document.getElementById("anexo");
  const fileName = document.getElementById("fileName");
  if (fileInput && fileName) {
    fileInput.addEventListener("change", () => {
      fileName.textContent = fileInput.files && fileInput.files[0]
        ? fileInput.files[0].name
        : "";
    });
  }

  // ---------------------------------------------------------
  // Formulário de orçamento -> envia por e-mail (fetch para o PHP)
  // ---------------------------------------------------------
  const form = document.getElementById("orcamentoForm");
  const status = document.getElementById("formStatus");
  const submitBtn = document.getElementById("submitBtn");
  const formTs = document.getElementById("formTs");
  const waFallback = document.getElementById("waFallback");

  // Marca o instante em que a página carregou — usado no servidor como
  // proteção antispam (envios "instantâneos demais" são de bots).
  if (formTs) formTs.value = String(Date.now());

  const showStatus = (msg, ok) => {
    if (!status) return;
    status.textContent = msg;
    status.classList.remove("is-ok", "is-error");
    status.classList.add("is-visible", ok ? "is-ok" : "is-error");
  };

  const buildResumo = (data) => [
    "Solicitação de orçamento — site MD Molas",
    `Nome: ${data.get("nome") || "-"}`,
    `Empresa: ${data.get("empresa") || "-"}`,
    `E-mail: ${data.get("email") || "-"}`,
    `WhatsApp: ${data.get("whatsapp") || "-"}`,
    `Segmento: ${data.get("segmento") || "-"}`,
    `Tipo de mola: ${data.get("tipo_mola") || "-"}`,
    `Quantidade estimada: ${data.get("quantidade") || "-"}`,
    `Possui desenho técnico: ${data.get("desenho") || "-"}`,
    `Possui amostra: ${data.get("amostra") || "-"}`,
    `Mensagem: ${data.get("mensagem") || "-"}`,
  ].join("\n");

  const MAX_FILE_BYTES = 20 * 1024 * 1024; // 20MB — mantido igual ao config.php

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const data = new FormData(form);
      const nome = (data.get("nome") || "").toString().trim();
      const email = (data.get("email") || "").toString().trim();
      const whatsapp = (data.get("whatsapp") || "").toString().trim();

      if (!nome || !email || !whatsapp) {
        showStatus("Preencha nome, e-mail e WhatsApp para continuar.", false);
        return;
      }

      const anexo = fileInput && fileInput.files ? fileInput.files[0] : null;
      if (anexo && anexo.size > MAX_FILE_BYTES) {
        showStatus("O arquivo anexado passa de 20MB. Escolha um arquivo menor.", false);
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Enviando…";
      }
      showStatus("Enviando sua solicitação…", true);

      try {
        const res = await fetch(form.action, {
          method: "POST",
          body: data,
        });

        let payload = null;
        try { payload = await res.json(); } catch (_) { /* resposta não era JSON */ }

        if (res.ok && payload && payload.ok) {
          showStatus(payload.message || "Solicitação enviada com sucesso!", true);
          form.reset();
          if (fileName) fileName.textContent = "";
          if (formTs) formTs.value = String(Date.now());
        } else {
          const msg = (payload && payload.message)
            ? payload.message
            : "Não foi possível enviar agora. Tente novamente ou fale pelo WhatsApp.";
          showStatus(msg, false);
        }
      } catch (err) {
        showStatus("Falha de conexão ao enviar. Verifique sua internet e tente novamente.", false);
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = "Enviar solicitação por e-mail";
        }
      }
    });
  }

  // ---------------------------------------------------------
  // Alternativa: abrir WhatsApp com um resumo do formulário
  // ---------------------------------------------------------
  if (waFallback && form) {
    waFallback.addEventListener("click", (e) => {
      e.preventDefault();
      const data = new FormData(form);
      const texto = encodeURIComponent(buildResumo(data));
      const numero = "5511962695040";
      window.open(`https://wa.me/${numero}?text=${texto}`, "_blank", "noopener");
    });
  }
})();
