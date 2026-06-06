// Associates Data
const associates = [
    { name: "Adisa Avdić-Küsmüş", field: "International Relations", affiliation: "Assistant Professor, Ankara Yildrim Beyazit University" },
    { name: "Elvis Avdić", field: "Business", affiliation: "Visiting Instructor of Entrepreneurial Studies" },
    { name: "Haris Avdić", field: "Political Science", affiliation: "Student at the University of Sarajevo" },
    { name: "Başak Akar Özer", field: "Political Science, Nationalism Studies", affiliation: "Assistant Professor, Ankara Yildrim Beyazit University" },
    { name: "Haris Alibašić", field: "Public Policy & Administration", affiliation: "Associate Professor, University of West Florida" },
    { name: "Samir Beharić", field: "Geographic Migration and Transition Studies", affiliation: "ABD, University of Bamberg, Balkans in Europe Policy Advisory Group Bamberg, Germany" },
    { name: "Sedad Bešlija", field: "History, Ottoman History", affiliation: "Researcher, Institut za istoriju u Sarajevu" },
    { name: "Adisa Busuladžić", field: "Political Science, Journalism", affiliation: "ABD, University of Sarajevo" },
    { name: "Admir Čavalić", field: "Economics", affiliation: "University of Tuzla; IPI Academy in Tuzla" },
    { name: "Lejla Delagić", field: "Comparative Religion", affiliation: "IIIT - Freelance researcher; IIMU Fellow" },
    { name: "Nihada Delibegović Džanić", field: "Cognitive Linguistics, Phraseology", affiliation: "Full Professor, University of Tuzla; Visiting Professor, Alpe Adria University in Klagenfurt, Austria" },
    { name: "Guliz Dinc", field: "Political Science & Public Administration", affiliation: "Assistant Professor, Ankara Yildirim Beyazit University" },
    { name: "Amir Durić", field: "Religious Studies", affiliation: "Assistant Dean for Religious and Spiritual Life, Syracuse University" },
    { name: "Suada A. Džogović", field: "International Relations, Diplomacy, Human Rights", affiliation: "Associate Professor, Haxhi Zeka University, Kosovo" },
    { name: "Mersiha Gadžo", field: "Journalism", affiliation: "Independent Journalist" },
    { name: "İbrahim Fevzi Güven", field: "Political Science and International Relations", affiliation: "Assistant Professor, Karabuk University, Turkey" },
    { name: "Adi Fejzic", field: "Linguistics", affiliation: "Associate Professor of Linguistics, Oryx Universal University" },
    { name: "Muris Hadžić", field: "Business", affiliation: "Associate Professor of Finance, Lake Forest College" },
    { name: "Elma Hadžić", field: "Clinical Psychology", affiliation: "Researcher & Business Owner" },
    { name: "Dr. Yucel Tugba Henderson", field: "Business, Administration & Leadership", affiliation: "Faculty Member, Central Michigan University" },
    { name: "Najla Hrustanović", field: "Counselor Education & Supervision", affiliation: "Program Director & Core Faculty, Antioch University" },
    { name: "Mirela Imširović", field: "International Relations, Political Science", affiliation: "Care with Association, Sarajevo, BiH" },
    { name: "Senad Jaskić", field: "Chiropractic / Acupuncture", affiliation: "VA Hospital, Chatasville, PA" },
    { name: "Ali Ihsan Kahraman", field: "International Relations", affiliation: "Istanbul Medeniyet University" },
    { name: "Alen Kalajdžija", field: "Linguistics", affiliation: "University of Sarajevo" },
    { name: "Georgio Konstandi", field: "Bosnian Genocide Studies", affiliation: "Bosnian Genocide Researcher and Writer" },
    { name: "Mirsad Kriještorac", field: "Political Science", affiliation: "Associate Professor, Broward College" },
    { name: "Nermina Kravić", field: "Psychiatry, War Trauma", affiliation: "School of Medicine, University in Tuzla" },
    { name: "Emin Lelić", field: "History, Ottoman History", affiliation: "Associate Professor, Salisbury University" },
    { name: "Adnan Mahmutović", field: "English Literature", affiliation: "Stockholm University" },
    { name: "Dr. Samir Mehanovic", field: "Veterinary Microbiology & Preventive Medicine", affiliation: "Researcher and Scientist, Iowa State University" },
    { name: "Mirela Muhić", field: "Innovation and Engineering", affiliation: "Denmark Technical University" },
    { name: "Furkan Ozkull", field: "Social Sciences and Humanities", affiliation: "Research Assistant, Istanbul University, Center for Genocide Studies" },
    { name: "Sabina Pačariz", field: "Politics & International Relations", affiliation: "Assistant Professor, Kings College; Northeastern University London" },
    { name: "Mirza Pojskić", field: "Neurosurgery", affiliation: "Philips University Marburg" },
    { name: "Emin Poljarević", field: "Sociology of Religion / Islamic Studies", affiliation: "Associate Professor, Uppsala University" },
    { name: "Jasmina Salčinović-Spahić", field: "Medicine, Healthcare Administration", affiliation: "BMC, VA, TIC" },
    { name: "Muhamed Šemoski", field: "Theology, Political Science, International Relations", affiliation: "University of Sarajevo; IZ BiH Sarajevo" },
    { name: "Ermin Sinanović", field: "Political Science, Religious Studies", affiliation: "Executive Director, CICW, Shenandoah University" },
    { name: "Esad Širbegović", field: "Computer Science", affiliation: "Independent Writer" },
    { name: "Mirza Tihić", field: "Entrepreneurship and Disability, Mathematics, Neuroscience", affiliation: "Assistant Professor, Syracuse University" },
    { name: "Selim Tiryakiol", field: "Psycholinguistics, Bilingualism", affiliation: "Arctic University of Norway; Istanbul Medeniyet University" },
    { name: "Mihaela Trișcă", field: "Law, History, International Humanitarian Law", affiliation: "Teaching Assistant, University George Emil Palade of Târgu Mureş; PhD Candidate" },
    { name: "Haris Variz", field: "Biology & Agronomy", affiliation: "Postdoctoral Fellow" },
    { name: "Emina Zoletić", field: "Sociology and Memory Studies", affiliation: "PhD ABD, University of Warsaw" },
    { name: "Admir Škodo", field: "History, Migration Issues", affiliation: "European University Institute, Brussels, Belgium" },
    { name: "Dzamil Bektovic", field: "Art History, Design", affiliation: "International Balkan University, Skopje, N. Macedonia" },
    { name: "Adnan Mestan", field: "Political Science and Public Administration", affiliation: "Coordinator, Balkan Studies; Senior Assistant, University of Sarajevo, Sarajevo, BiH" },
    { name: "Zilka Spahić-Šiljak", field: "Gender Studies, Religion, Human Rights, Peacebuilding", affiliation: "Director, TPO Foundation; Professor, University of Zenica; Visiting Professor, Roehampton, Zenica, BiH" }
];

// Populate Associates Grid
function populateAssociates(filter = '') {
    const grid = document.getElementById('associatesGrid');
    grid.innerHTML = '';

    const filtered = associates.filter(associate =>
        associate.name.toLowerCase().includes(filter.toLowerCase()) ||
        associate.field.toLowerCase().includes(filter.toLowerCase())
    );

    if (filtered.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #666;">No associates found matching your search.</p>';
        return;
    }

    filtered.forEach(associate => {
        const card = document.createElement('a');
        card.href = `associate-detail.html?id=${associate.slug || associate.name.toLowerCase().replace(/\s+/g, '-').replace(/[^\w\-]/g, '')}`;
        card.className = 'associate-card-link';
        card.innerHTML = `
            <div class="associate-card">
                <div class="associate-image">
                    <img src="../${associate.imageUrl || 'images/placeholder.png'}" alt="${associate.name}" />
                </div>
                <div class="associate-info">
                    <h3>${associate.name}</h3>
                    <p class="field"><strong>${associate.field}</strong></p>
                    <p class="affiliation">${associate.affiliation}</p>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

// Search Functionality
document.addEventListener('DOMContentLoaded', () => {
    populateAssociates();

    const searchInput = document.getElementById('associateSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            populateAssociates(e.target.value);
        });
    }
});
