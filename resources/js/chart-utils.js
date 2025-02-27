export function createSVGBar(id, numberOfVotes, sumOfVotes, correctVote = false) {
    const namespaceURI = 'http://www.w3.org/2000/svg';

    if (! numberOfVotes) return false;
    
    let svg = document.createElementNS(namespaceURI, 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '10');
    svg.setAttribute('class', 'text-blue-200 dark:text-gray-200');

    let rect = document.createElementNS(namespaceURI, 'rect');
    rect.setAttribute('x', '0');
    rect.setAttribute('y', '0');
    rect.setAttribute('rx', '4');
    rect.setAttribute('ry', '4');
    rect.setAttribute('width', '0');
    rect.setAttribute('height', '10');

    correctVote === true
        ? rect.setAttribute('fill', '#6366F1')
        : rect.setAttribute('fill', 'currentColor');

    svg.appendChild(rect);

    const width = (numberOfVotes / sumOfVotes) * 100;
    const scale = 0.8; // scale the widh down so the label will fit

    const label = document.createElementNS(namespaceURI, 'text');
    label.setAttribute('x', width * scale + 2 + '%');
    label.setAttribute('y', '10');
    label.setAttribute('fill', 'currentColor');
    label.setAttribute('class', 'fill-current text-gray-500 dark:text-gray-400');
    label.style.fontSize = '12px';
    label.textContent = width.toFixed(1) + '%';
    svg.appendChild(label);

    rect.setAttribute('width', width * scale + '%');

    const element = document.getElementById('bar-id-' + id);
    element && element.appendChild(svg);
}