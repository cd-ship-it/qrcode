// QR code rendering for the generator page.
(function (global) {
  const SIZE = 1200;
  // Resolve the logo relative to this script so it works at the site root or in a subdirectory.
  const LOGO_SRC = document.currentScript
    ? new URL("../Xpt-ID2015_color.png", document.currentScript.src).href
    : "Xpt-ID2015_color.png";

  const STYLES = {
    brand: {
      dotsColor: "#0b5fa5",
      dotsType: "rounded",
      cornerSquareColor: "#0b5fa5",
      cornerDotColor: "#f7941d",
      bgColor: "#ffffff",
      logo: "color",
      banner: null
    },
    classic: {
      dotsColor: "#000000",
      dotsType: "square",
      cornerSquareColor: "#000000",
      cornerDotColor: "#000000",
      bgColor: "#ffffff",
      logo: "bw",
      banner: null
    },
    scanme: {
      dotsColor: "#0b5fa5",
      dotsType: "rounded",
      cornerSquareColor: "#0b5fa5",
      cornerDotColor: "#f7941d",
      bgColor: "#ffffff",
      logo: "color",
      banner: { text: "SCAN ME", bg: "#0b5fa5", fg: "#ffffff" }
    },
    "classic-scanme": {
      dotsColor: "#000000",
      dotsType: "square",
      cornerSquareColor: "#000000",
      cornerDotColor: "#000000",
      bgColor: "#ffffff",
      logo: "bw",
      banner: { text: "SCAN ME", bg: "#000000", fg: "#ffffff" }
    }
  };

  let bwLogoCache = null;
  let colorLogoCache = null;

  function loadImage(src) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.crossOrigin = "anonymous";
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
      img.src = src;
    });
  }

  function blobToImage(blob) {
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(blob);
      const img = new Image();
      img.onload = () => {
        URL.revokeObjectURL(url);
        resolve(img);
      };
      img.onerror = () => reject(new Error("Failed to decode generated QR code image"));
      img.src = url;
    });
  }

  // qr-code-styling renders canvas output by rasterizing an internal SVG; browsers refuse
  // to load externally-referenced images inside an SVG used that way, so the logo must be
  // passed in as a self-contained data URI rather than a plain file path.
  async function getColorLogo() {
    if (colorLogoCache) return colorLogoCache;
    const img = await loadImage(LOGO_SRC);
    const canvas = document.createElement("canvas");
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    colorLogoCache = canvas.toDataURL("image/png");
    return colorLogoCache;
  }

  async function getBwLogo() {
    if (bwLogoCache) return bwLogoCache;
    const img = await loadImage(LOGO_SRC);
    const canvas = document.createElement("canvas");
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const d = imgData.data;
    for (let i = 0; i < d.length; i += 4) {
      if (d[i + 3] > 0) {
        d[i] = 0;
        d[i + 1] = 0;
        d[i + 2] = 0;
      }
    }
    ctx.putImageData(imgData, 0, 0);
    bwLogoCache = canvas.toDataURL("image/png");
    return bwLogoCache;
  }

  async function buildStyleCanvas(style, data) {
    let logoSrc;
    try {
      logoSrc = style.logo === "bw" ? await getBwLogo() : await getColorLogo();
    } catch (e) {
      throw new Error(`Could not load the logo image (${e.message || e}). Check your connection and try again.`);
    }

    const qrCode = new QRCodeStyling({
      width: SIZE,
      height: SIZE,
      type: "canvas",
      data: data,
      image: logoSrc,
      margin: 20,
      qrOptions: {
        errorCorrectionLevel: "H"
      },
      imageOptions: {
        crossOrigin: "anonymous",
        margin: 16,
        imageSize: 0.22
      },
      dotsOptions: {
        color: style.dotsColor,
        type: style.dotsType
      },
      cornersSquareOptions: {
        color: style.cornerSquareColor,
        type: "extra-rounded"
      },
      cornersDotOptions: {
        color: style.cornerDotColor
      },
      backgroundOptions: {
        color: style.bgColor
      }
    });

    const blob = await qrCode.getRawData("png");
    const qrImg = await blobToImage(blob);

    const finalCanvas = document.createElement("canvas");
    finalCanvas.width = SIZE;

    if (style.banner) {
      const bannerHeight = 180;
      finalCanvas.height = SIZE + bannerHeight;
      const ctx = finalCanvas.getContext("2d");
      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height);
      ctx.fillStyle = style.banner.bg;
      ctx.fillRect(0, 0, SIZE, bannerHeight);
      ctx.fillStyle = style.banner.fg;
      ctx.font = "bold 110px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.fillText(style.banner.text, SIZE / 2, bannerHeight / 2);
      ctx.drawImage(qrImg, 0, bannerHeight, SIZE, SIZE);
    } else {
      finalCanvas.height = SIZE;
      const ctx = finalCanvas.getContext("2d");
      ctx.drawImage(qrImg, 0, 0, SIZE, SIZE);
    }

    return finalCanvas;
  }

  function downloadCanvas(canvas, filename) {
    const link = document.createElement("a");
    link.download = filename;
    link.href = canvas.toDataURL("image/png");
    link.click();
  }

  global.QRGen = { SIZE, STYLES, buildStyleCanvas, downloadCanvas };
})(window);
