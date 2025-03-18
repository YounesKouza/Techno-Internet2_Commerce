// Recherhche Menu
function search() {
    var input, filter, ul, li, a, i;
    input = document.getElementById("search-item");
    filter = input.value.toUpperCase();
    ul = document.getElementById("myMenu");
    li = ul.getElementsByTagName("li");
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName("a")[0];
        if (a.name.toUpperCase().indexOf(filter) > -1) {
            console.log(i)

                li[i].style.display = "";
        } else {
            if (i!=0)
                li[i].style.display = "none";
        }
    }
}

// Recherche Vêtements
function searchVet() {
    $(document).ready(function () {
        $("#search_Vetement").on("keyup", function () {
            var value = $(this).val().toLowerCase();
            $("#prod div").filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
}