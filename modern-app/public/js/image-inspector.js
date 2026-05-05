(function () {
    const image = document.getElementById('inspector-image');
    const histogramCanvas = document.getElementById('image-histogram-canvas');

    if (!image || !histogramCanvas) {
        return;
    }

    const renderHistogram = () => {
        const width = image.naturalWidth || image.width;
        const height = image.naturalHeight || image.height;

        if (!width || !height) {
            return;
        }

        const sourceCanvas = document.createElement('canvas');
        sourceCanvas.width = width;
        sourceCanvas.height = height;

        const sourceContext = sourceCanvas.getContext('2d');

        if (!sourceContext) {
            return;
        }

        sourceContext.drawImage(image, 0, 0, width, height);
        const pixelData = sourceContext.getImageData(0, 0, width, height).data;

        const bins = 256;
        const red = new Array(bins).fill(0);
        const green = new Array(bins).fill(0);
        const blue = new Array(bins).fill(0);

        for (let i = 0; i < pixelData.length; i += 4) {
            red[pixelData[i]] += 1;
            green[pixelData[i + 1]] += 1;
            blue[pixelData[i + 2]] += 1;
        }

        const context = histogramCanvas.getContext('2d');

        if (!context) {
            return;
        }

        const canvasWidth = histogramCanvas.width;
        const canvasHeight = histogramCanvas.height;
        const maxValue = Math.max(
            ...red,
            ...green,
            ...blue,
            1
        );

        context.clearRect(0, 0, canvasWidth, canvasHeight);
        context.fillStyle = '#f6faff';
        context.fillRect(0, 0, canvasWidth, canvasHeight);

        const drawChannel = (channel, color) => {
            context.beginPath();
            context.strokeStyle = color;
            context.lineWidth = 1.35;

            for (let i = 0; i < bins; i++) {
                const x = (i / (bins - 1)) * canvasWidth;
                const normalized = channel[i] / maxValue;
                const y = canvasHeight - normalized * (canvasHeight - 16);

                if (i === 0) {
                    context.moveTo(x, y);
                } else {
                    context.lineTo(x, y);
                }
            }

            context.stroke();
        };

        drawChannel(red, 'rgba(214, 58, 58, 0.92)');
        drawChannel(green, 'rgba(37, 142, 75, 0.92)');
        drawChannel(blue, 'rgba(44, 96, 199, 0.92)');
    };

    if (image.complete) {
        renderHistogram();
    } else {
        image.addEventListener('load', renderHistogram, { once: true });
    }
})();
