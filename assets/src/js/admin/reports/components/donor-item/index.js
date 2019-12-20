import PropTypes from 'prop-types'
import './style.scss'

const DonorItem = ({image, name, email, count, total}) => {
    return (
        <div>
            <img src={image} />
            <div>
                <p><strong>{name}</strong></p>
                <p>{email}</p>
            </div>
            <div>
                <p>{count}</p>
                <p>{total}</p>
            </div>
        </div>
    )
}

DonotItem.propTypes = {
    image: PropTypes.string.isRequired,
    name: PropTypes.string.isRequired,
    email: PropTypes.string.isRequired,
    count: PropTypes.string.isRequired,
    total: PropTypes.string.isRequired
}

export default DonorItem