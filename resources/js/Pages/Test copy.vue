<script setup>

</script>

<template>
    <section id="store" class="store-section container reveal-item">
        <div class="store-header">
            <div class="eyebrow-badge">
                <span class="eyebrow-dot"></span>
                <span class="eyebrow-text">Online Store</span>
            </div>
            <h2 class="store-title">Hardware Kits</h2>
            <p class="store-subtitle">
                No cloud tethers, no forced subscriptions, just pure
                local compute that you control. Whether you want to plug-and-play or tinker like us, our store has
                something for you. And if it does not, contact us and we'll see what we can do!
            </p>
        </div>

        <div id="productGrid" class="product-grid">
            <div class="bezel-card-outer product-card reveal-item">
                <div class="bezel-card-inner">
                    <div class="product-badge">Pre-Configured</div>
                    <div class="product-image-placeholder">
                        <i class="ph-light ph-hard-drives"></i>
                    </div>
                    <h3 class="product-title">Smart Home Core</h3>
                    <p class="product-description">
                        Includes hardware, installation and
                        pre-configuration of Proxmox VE running Home
                        Assistant OS. The foundation for your sovereign
                        home network.
                    </p>
                    <div class="product-meta">
                        <span class="product-price"></span>
                        <span class="product-status"><span class="status-dot"></span>Coming Soon</span>
                    </div>
                </div>
            </div>

            <div class="bezel-card-outer product-card reveal-item">
                <div class="bezel-card-inner">
                    <div class="product-badge">Your Hardware</div>
                    <div class="product-image-placeholder">
                        <i class="ph-light ph-hard-drives"></i>
                    </div>
                    <h3 class="product-title">
                        Smart Home Core (Software & Configuration)
                    </h3>
                    <p class="product-description">
                        Installation and configuration of Proxmox VE
                        running Home Assistant OS, using hardware
                        provided by you.
                    </p>
                    <a href="#" style="
                color: var(--color-accent-primary);
                font-size: 0.75rem;
                margin-bottom: 10px;
                display: block;
              ">Check hardware requirements</a>
                    <div class="product-meta">
                        <span class="product-price"></span>
                        <span class="product-status"><span class="status-dot"></span>Coming Soon</span>
                    </div>
                </div>
            </div>

            <div class="bezel-card-outer product-card reveal-item">
                <div class="bezel-card-inner">
                    <div class="product-badge">Blueprint #1 Kit</div>
                    <div class="product-image-placeholder">
                        <i class="ph-light ph-cpu"></i>
                    </div>
                    <h3 class="product-title">
                        Legacy Alarm Conversion Kit
                    </h3>
                    <p class="product-description">
                        Opto-isolated interface board to safely bridge
                        high-voltage alarm states into 3.3V
                        microcontrollers.
                    </p>
                    <div class="product-meta">
                        <span class="product-price"></span>
                        <span class="product-status"><span class="status-dot"></span>Coming Soon</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
/* --- Store Section --- */
.store-section {
    position: relative;
    z-index: 10;
    padding-bottom: 4rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    height: 100%;
    width: 100%;
    scroll-snap-align: start;
    scroll-snap-stop: always;
}

.store-header {
    text-align: center;
    margin-bottom: 56px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.store-title {
    font-family: var(--font-display);
    font-size: 3rem;
    font-weight: 800;
    line-height: 1.2;
    color: var(--color-text-primary);
    margin: 16px 0;
}

.store-subtitle {
    color: var(--color-text-secondary);
    max-width: 650px;
    line-height: 1.6;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}

.product-card .bezel-card-inner {
    padding: 24px;
    align-items: stretch;
}

.product-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 136, 0, 0.08);
    border: 1px solid rgba(255, 136, 0, 0.25);
    color: var(--color-accent-primary);
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 2;
}

.product-image-placeholder {
    height: 200px;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--color-border-outer);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
}

.product-image-placeholder i {
    font-size: 3.5rem;
    color: var(--color-accent-primary);
    opacity: 0.6;
    transition: all 0.5s var(--ease-premium);
}

.product-card:hover .product-image-placeholder i {
    transform: scale(1.1);
    opacity: 1;
}

.product-title {
    font-family: var(--font-display);
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--color-text-primary);
    margin-bottom: 10px;
}

.product-description {
    color: var(--color-text-secondary);
    font-size: 0.88rem;
    line-height: 1.5;
    margin-bottom: 20px;
    flex-grow: 1;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-top: 15px;
    border-top: 1px solid var(--color-border-inner);
}

.product-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text-primary);
    font-family: var(--font-display);
}

.product-status {
    font-size: 0.75rem;
    color: var(--color-accent-secondary);
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
}

.status-dot {
    width: 6px;
    height: 6px;
    background: var(--color-accent-secondary);
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 6px var(--color-accent-secondary);
}

@media (max-width: 768px) {
    .store-section {
        height: auto;
        min-height: auto;
        scroll-snap-align: none;
        scroll-snap-stop: normal;
        padding-top: 80px;
        padding-bottom: 80px;
    }

    .product-grid {
        grid-template-columns: 1fr !important;
        gap: 24px !important;
    }

    .bezel-card-inner {
        padding: 28px;
    }
}
</style>