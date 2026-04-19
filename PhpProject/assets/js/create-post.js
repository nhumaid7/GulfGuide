document.gerElementById("postForm").addEventListener("submit", function(e){
    e.preventDefault();
    const formData= new FormData(this);

    fetch("Post.php", {
        method: "POST",
        body: formData 
    })
    .then(res => res.text())
    .then(data => {
        document.getElementById("response").innerText = data;
    })
    .catch(err => {
        console.error(err);
    });

})