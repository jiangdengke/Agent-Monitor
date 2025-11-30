<script setup>
</script>

<template>
  <footer class="public-footer">
    <div class="footer-content">
      <p class="copyright">© {{ new Date().getFullYear() }} 系统监控平台</p>
    </div>
  </footer>
</template>

<style scoped>
.public-footer {
  background: #fff;
  border-top: 1px solid #e5e7eb;
  padding: 24px 0;
}

.footer-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 24px;
  text-align: center;
}

.copyright {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}
</style>
