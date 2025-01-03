function emitToastErr(title){
    Swal.fire({
        title: title,
        text: "Si el problema persiste, ponte en contacto con el administrador del sitio.",
        icon: "error",
        toast: true,
        position: "top-end",
    });
}

function emitToastConnection(attempt,retryTime){
    Swal.fire({
        text: "Intentando conectarse al servidor interno... ("+`${attempt}`+")",
        timer: retryTime,
        position: "top-end",
        toast: true,
        showConfirmButton: false, 
        didOpen: () => {
          Swal.showLoading();
        },
      });
}

function emitPersToast(text,icon,extra = false,timer = 3000){
  Swal.fire({
      title: text,
      text: extra,
      position: "top-end",
      toast: true,
      showConfirmButton: false, 
      icon: icon,
      timer: timer,
    });
}
